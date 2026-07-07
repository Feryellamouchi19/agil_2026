package com.userapp.service;

import com.google.gson.JsonObject;
import com.google.gson.JsonParser;
import com.sun.net.httpserver.HttpServer;
import com.userapp.entite.Role;
import com.userapp.entite.User;

import java.awt.Desktop;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.InetSocketAddress;
import java.net.URI;
import java.net.URLEncoder;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.nio.charset.StandardCharsets;
import java.util.concurrent.CompletableFuture;
import java.util.concurrent.CountDownLatch;
import java.util.concurrent.TimeUnit;
import java.util.concurrent.atomic.AtomicReference;

/**
 * Google OAuth 2.0 service for desktop application sign-in.
 *
 * HOW TO CONFIGURE:
 * 1. Go to https://console.cloud.google.com
 * 2. Create a project, enable "People API"
 * 3. Create OAuth 2.0 credentials (Desktop Application type)
 * 4. Copy your Client ID and Client Secret below.
 */
public class GoogleAuthService {

    // Credentials loaded from config.properties
    private static String CLIENT_ID;
    private static String CLIENT_SECRET;

    static {
        try (InputStream input = GoogleAuthService.class.getClassLoader().getResourceAsStream("config.properties")) {
            if (input == null) {
                System.err.println("Sorry, unable to find config.properties");
            } else {
                java.util.Properties prop = new java.util.Properties();
                prop.load(input);
                CLIENT_ID = prop.getProperty("google.client.id");
                CLIENT_SECRET = prop.getProperty("google.client.secret");
            }
        } catch (IOException ex) {
            ex.printStackTrace();
        }
    }

    private static final String REDIRECT_URI  = "http://localhost:8989/callback";
    private static final String AUTH_URL      = "https://accounts.google.com/o/oauth2/v2/auth";
    private static final String TOKEN_URL     = "https://oauth2.googleapis.com/token";
    private static final String USERINFO_URL  = "https://www.googleapis.com/oauth2/v3/userinfo";
    private static final int    CALLBACK_PORT = 8989;

    private final DatabaseService dbService;

    public GoogleAuthService(DatabaseService dbService) {
        this.dbService = dbService;
    }

    /**
     * Starts the full Google OAuth flow:
     *  1. Opens the browser with the Google login page
     *  2. Waits for Google to redirect back with an auth code
     *  3. Exchanges the code for an access token
     *  4. Fetches the user's profile (name + email)
     *  5. Looks up (or creates) the user in the database
     *
     * @return The authenticated User object, or null on failure.
     */
    public User signIn() throws Exception {
        // Step 1: Build authorization URL
        String authUrl = AUTH_URL + "?client_id=" + URLEncoder.encode(CLIENT_ID, StandardCharsets.UTF_8)
                + "&redirect_uri=" + URLEncoder.encode(REDIRECT_URI, StandardCharsets.UTF_8)
                + "&response_type=code"
                + "&scope=" + URLEncoder.encode("openid email profile", StandardCharsets.UTF_8)
                + "&access_type=offline"
                + "&prompt=select_account";

        // Step 2: Start a local HTTP server to capture the callback
        CountDownLatch latch = new CountDownLatch(1);
        AtomicReference<String> authCodeRef = new AtomicReference<>(null);
        AtomicReference<String> errorRef    = new AtomicReference<>(null);

        HttpServer server = HttpServer.create(new InetSocketAddress(CALLBACK_PORT), 0);
        server.createContext("/callback", exchange -> {
            String query = exchange.getRequestURI().getQuery();
            String html;
            if (query != null && query.contains("code=")) {
                String code = extractParam(query, "code");
                authCodeRef.set(code);
                html = "<html><body style='font-family:Arial;text-align:center;margin-top:80px'>"
                        + "<h2 style='color:#e11d48'>✅ Authentification réussie !</h2>"
                        + "<p>Vous pouvez fermer cet onglet et revenir à l'application.</p></body></html>";
            } else {
                errorRef.set("Accès refusé ou erreur Google Auth.");
                html = "<html><body style='font-family:Arial;text-align:center;margin-top:80px'>"
                        + "<h2 style='color:#dc2626'>❌ Accès refusé</h2>"
                        + "<p>Vous pouvez fermer cet onglet.</p></body></html>";
            }
            byte[] response = html.getBytes(StandardCharsets.UTF_8);
            exchange.getResponseHeaders().add("Content-Type", "text/html; charset=UTF-8");
            exchange.sendResponseHeaders(200, response.length);
            try (OutputStream os = exchange.getResponseBody()) {
                os.write(response);
            }
            latch.countDown();
        });
        server.setExecutor(null);
        server.start();

        // Step 3: Open browser
        if (Desktop.isDesktopSupported()) {
            Desktop.getDesktop().browse(new URI(authUrl));
        } else {
            throw new Exception("Impossible d'ouvrir le navigateur. Veuillez ouvrir manuellement :\n" + authUrl);
        }

        // Step 4: Wait for callback (max 2 minutes)
        boolean received = latch.await(120, TimeUnit.SECONDS);
        server.stop(1);

        if (!received || authCodeRef.get() == null) {
            String error = errorRef.get();
            throw new Exception(error != null ? error : "La connexion Google a expiré. Veuillez réessayer.");
        }

        // Step 5: Exchange auth code for tokens
        String accessToken = exchangeCodeForToken(authCodeRef.get());

        // Step 6: Fetch user info
        JsonObject profile = fetchUserInfo(accessToken);
        String email = profile.get("email").getAsString();
        String name  = profile.has("name") ? profile.get("name").getAsString() : email;

        // Step 7: Find or auto-create user in DB (Option B: default role = Client)
        return findOrCreateGoogleUser(email, name);
    }

    // ------------------------------------------------------------------
    //  PRIVATE HELPERS
    // ------------------------------------------------------------------

    private String exchangeCodeForToken(String code) throws Exception {
        String body = "code=" + URLEncoder.encode(code, StandardCharsets.UTF_8)
                + "&client_id=" + URLEncoder.encode(CLIENT_ID, StandardCharsets.UTF_8)
                + "&client_secret=" + URLEncoder.encode(CLIENT_SECRET, StandardCharsets.UTF_8)
                + "&redirect_uri=" + URLEncoder.encode(REDIRECT_URI, StandardCharsets.UTF_8)
                + "&grant_type=authorization_code";

        HttpClient client = HttpClient.newHttpClient();
        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create(TOKEN_URL))
                .header("Content-Type", "application/x-www-form-urlencoded")
                .POST(HttpRequest.BodyPublishers.ofString(body))
                .build();

        HttpResponse<String> response = client.send(request, HttpResponse.BodyHandlers.ofString());
        JsonObject json = JsonParser.parseString(response.body()).getAsJsonObject();

        if (!json.has("access_token")) {
            throw new Exception("Impossible d'obtenir le token Google. Vérifiez vos identifiants client.");
        }
        return json.get("access_token").getAsString();
    }

    private JsonObject fetchUserInfo(String accessToken) throws Exception {
        HttpClient client = HttpClient.newHttpClient();
        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create(USERINFO_URL))
                .header("Authorization", "Bearer " + accessToken)
                .GET()
                .build();

        HttpResponse<String> response = client.send(request, HttpResponse.BodyHandlers.ofString());
        return JsonParser.parseString(response.body()).getAsJsonObject();
    }

    private User findOrCreateGoogleUser(String email, String name) throws Exception {
        // Look for existing user by email
        java.util.List<User> allUsers = dbService.loadAllUsers();
        for (User u : allUsers) {
            if (u.getEmail().equalsIgnoreCase(email.trim())) {
                return u;
            }
        }

        // Not found → auto-create with role CLIENT and a random password
        String tempPassword = String.valueOf((int)(Math.random() * 90000000) + 10000000);
        User newUser = new User(0, name, email, tempPassword, Role.CLIENT, "");
        dbService.insertUser(newUser);

        // Reload to get the assigned ID
        allUsers = dbService.loadAllUsers();
        for (User u : allUsers) {
            if (u.getEmail().equalsIgnoreCase(email.trim())) {
                return u;
            }
        }
        throw new Exception("Erreur lors de la création du compte Google.");
    }

    private String extractParam(String query, String paramName) {
        for (String param : query.split("&")) {
            String[] pair = param.split("=", 2);
            if (pair.length == 2 && pair[0].equals(paramName)) {
                return pair[1];
            }
        }
        return null;
    }
}
