package com.userapp.service;

import com.userapp.entite.User;
import com.userapp.controller.EmailSimulationController;

import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.stage.Modality;
import javafx.stage.Stage;
import javafx.stage.Window;

import java.io.File;
import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Paths;
import java.nio.file.StandardOpenOption;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;

public class EmailService {
    private static final String DATA_DIR = "data";
    private static final String EMAIL_LOG_FILE = DATA_DIR + "/sent_emails.log";

    public EmailService() {
        initDataDirectory();
    }

    private void initDataDirectory() {
        File dir = new File(DATA_DIR);
        if (!dir.exists()) {
            dir.mkdirs();
        }
    }

    public void sendCredentialsEmail(Window ownerWindow, User recipient, String plainPassword) {
        // 1. Log to file
        logEmail(recipient, plainPassword);

        // 2. Load and launch JavaFX email simulation dialog
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/com/userapp/view/email_simulation.fxml"));
            Parent root = loader.load();
            
            EmailSimulationController controller = loader.getController();
            controller.initData(recipient.getEmail(), recipient.getName(), plainPassword);

            Stage stage = new Stage();
            stage.initModality(Modality.WINDOW_MODAL);
            if (ownerWindow != null) {
                stage.initOwner(ownerWindow);
            }
            stage.setTitle("Envoi d'e-mail sécurisé");
            
            Scene scene = new Scene(root);
            scene.getStylesheets().add(getClass().getResource("/com/userapp/css/styles.css").toExternalForm());
            
            stage.setScene(scene);
            stage.setResizable(false);
            stage.showAndWait();
        } catch (IOException e) {
            System.err.println("Error loading email simulation FXML: " + e.getMessage());
            e.printStackTrace();
        }
    }

    private void logEmail(User recipient, String plainPassword) {
        String timestamp = LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss"));
        String emailContent = String.format(
            "==================================================\n" +
            "Date/Heure: %s\n" +
            "Destinataire: %s (%s)\n" +
            "Sujet: Vos identifiants de connexion - App\n" +
            "--------------------------------------------------\n" +
            "Bonjour %s,\n\n" +
            "Votre compte a été créé avec succès par l'administrateur.\n" +
            "Voici vos identifiants pour vous connecter :\n\n" +
            "  - Adresse e-mail: %s\n" +
            "  - Mot de passe: %s\n\n" +
            "Pour votre sécurité, nous vous conseillons de modifier ce mot de passe\n" +
            "dès votre première connexion depuis votre profil.\n\n" +
            "Cordialement,\n" +
            "L'équipe d'Administration\n" +
            "==================================================\n\n",
            timestamp, recipient.getEmail(), recipient.getName(), recipient.getName(), recipient.getEmail(), plainPassword
        );

        try {
            Files.writeString(
                Paths.get(EMAIL_LOG_FILE), 
                emailContent, 
                StandardOpenOption.CREATE, 
                StandardOpenOption.APPEND
            );
        } catch (IOException e) {
            System.err.println("Error writing to email log file: " + e.getMessage());
        }
    }
}
