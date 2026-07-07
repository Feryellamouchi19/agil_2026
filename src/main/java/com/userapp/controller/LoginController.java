package com.userapp.controller;

import com.userapp.entite.User;
import com.userapp.tools.UserSession;
import com.userapp.service.UserService;
import com.userapp.service.EmailService;
import com.userapp.service.GoogleAuthService;
import com.userapp.service.DatabaseService;

import javafx.application.Platform;
import javafx.concurrent.Task;
import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.control.PasswordField;
import javafx.scene.control.TextField;
import javafx.scene.input.KeyEvent;
import javafx.scene.input.KeyCode;
import javafx.stage.Stage;

import java.io.IOException;

public class LoginController {
    private final UserService       userService   = new UserService();
    private final EmailService      emailService  = new EmailService();
    private final GoogleAuthService googleAuth    = new GoogleAuthService(new DatabaseService());

    @FXML private TextField     emailField;
    @FXML private PasswordField passwordField;
    @FXML private Label         errorLabel;
    @FXML private Button        loginButton;
    @FXML private Button        googleLoginButton;

    @FXML
    void handleLogin(ActionEvent event) {
        performLogin();
    }

    @FXML
    void handleKeyPressed(KeyEvent event) {
        if (event.getCode() == KeyCode.ENTER) {
            performLogin();
        }
    }

    @FXML
    void handleGoogleLogin(ActionEvent event) {
        setLoading(true);
        errorLabel.setText("Ouverture du navigateur Google...");

        // Run OAuth flow off the UI thread
        Task<User> task = new Task<>() {
            @Override
            protected User call() throws Exception {
                return googleAuth.signIn();
            }
        };

        task.setOnSucceeded(e -> {
            User user = task.getValue();
            if (user != null) {
                Platform.runLater(() -> openDashboard(user));
            } else {
                Platform.runLater(() -> {
                    setLoading(false);
                    errorLabel.setText("Échec de la connexion Google.");
                });
            }
        });

        task.setOnFailed(e -> {
            Platform.runLater(() -> {
                setLoading(false);
                Throwable err = task.getException();
                errorLabel.setText("Erreur Google : " + (err != null ? err.getMessage() : "Inconnue"));
            });
        });

        Thread thread = new Thread(task);
        thread.setDaemon(true);
        thread.start();
    }

    private void performLogin() {
        String email    = emailField.getText().trim();
        String password = passwordField.getText().trim();

        if (email.isEmpty()) {
            errorLabel.setText("L'adresse e-mail est requise.");
            return;
        }
        if (password.isEmpty()) {
            errorLabel.setText("Le mot de passe est requis.");
            return;
        }

        User user = userService.authenticate(email, password);
        if (user != null) {
            errorLabel.setText("");
            openDashboard(user);
        } else {
            errorLabel.setText("Email ou mot de passe incorrect.");
        }
    }

    private void openDashboard(User user) {
        UserSession.getInstance().setCurrentUser(user);
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/com/userapp/view/dashboard.fxml"));
            Parent root = loader.load();

            DashboardController dashboardController = loader.getController();
            dashboardController.initData(userService, emailService);

            Stage stage = new Stage();
            stage.setTitle("Application de Gestion - " + user.getRole().getDisplayName());

            Scene scene = new Scene(root);
            scene.getStylesheets().add(getClass().getResource("/com/userapp/css/styles.css").toExternalForm());

            stage.setScene(scene);
            stage.show();

            Stage currentStage = (Stage) loginButton.getScene().getWindow();
            currentStage.close();

        } catch (IOException e) {
            errorLabel.setText("Erreur lors du chargement du tableau de bord : " + e.getMessage());
            e.printStackTrace();
        }
    }

    private void setLoading(boolean loading) {
        loginButton.setDisable(loading);
        googleLoginButton.setDisable(loading);
        googleLoginButton.setText(loading ? "Connexion en cours..." : "Continuer avec Google");
    }
}
