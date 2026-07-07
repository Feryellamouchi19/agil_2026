package com.userapp.controller;

import com.userapp.entite.User;
import com.userapp.service.UserService;
import com.userapp.tools.UserSession;

import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.image.Image;
import javafx.scene.image.ImageView;
import javafx.scene.paint.Color;
import javafx.scene.shape.Circle;
import javafx.stage.FileChooser;

import java.io.File;
import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.nio.file.StandardCopyOption;

public class UserProfileController {
    private UserService userService;
    private User currentUser;

    // Photo section
    @FXML private Circle    avatarCircle;
    @FXML private Label     avatarInitialsLabel;
    @FXML private ImageView profilePhotoView;
    @FXML private Button    uploadPhotoBtn;
    @FXML private Button    removePhotoBtn;

    // Info fields
    @FXML private TextField     nameField;
    @FXML private TextField     emailField;
    @FXML private TextField     telField;
    @FXML private TextField     roleField;
    @FXML private PasswordField oldPassField;
    @FXML private PasswordField newPassField;
    @FXML private PasswordField confirmPassField;
    @FXML private Button        saveBtn;

    // Local photo storage folder (inside the app resources)
    private static final String PHOTOS_DIR = "src/main/resources/com/userapp/photos/";

    public void initData(UserService userService) {
        this.userService = userService;
        this.currentUser = UserSession.getInstance().getCurrentUser();

        // Fill fields
        nameField.setText(currentUser.getName());
        emailField.setText(currentUser.getEmail());
        if (currentUser.getTel() != null) {
            telField.setText(currentUser.getTel());
        }
        roleField.setText(currentUser.getRole().getDisplayName());

        // Set initials in avatar
        String initials = buildInitials(currentUser.getName());
        avatarInitialsLabel.setText(initials);

        // Try to load existing photo
        loadUserPhoto();
    }

    // ------------------------------------------------------------------
    //  PHOTO UPLOAD
    // ------------------------------------------------------------------

    @FXML
    void handleUploadPhoto(ActionEvent event) {
        FileChooser fileChooser = new FileChooser();
        fileChooser.setTitle("Choisir une photo de profil");
        fileChooser.getExtensionFilters().addAll(
            new FileChooser.ExtensionFilter("Images", "*.jpg", "*.jpeg", "*.png", "*.gif")
        );

        File selected = fileChooser.showOpenDialog(uploadPhotoBtn.getScene().getWindow());
        if (selected == null) return;

        // Check file size (max 5 MB)
        if (selected.length() > 5 * 1024 * 1024) {
            showAlert("Fichier trop lourd", "La photo ne doit pas dépasser 5 Mo.", Alert.AlertType.ERROR);
            return;
        }

        try {
            // Create photos directory if it doesn't exist
            Path photosDir = Paths.get(PHOTOS_DIR);
            Files.createDirectories(photosDir);

            // Determine extension
            String fileName = selected.getName();
            String ext = fileName.contains(".") ? fileName.substring(fileName.lastIndexOf('.')) : ".jpg";

            // Save as user_{id}{ext}
            Path dest = photosDir.resolve("user_" + currentUser.getId() + ext);
            // Remove old photos for this user (other extensions)
            deleteOldPhotos();
            Files.copy(selected.toPath(), dest, StandardCopyOption.REPLACE_EXISTING);

            // Display the new photo
            Image img = new Image(dest.toUri().toString(), true);
            showPhoto(img);

            showAlert("Succès", "Votre photo de profil a été mise à jour !", Alert.AlertType.INFORMATION);

        } catch (IOException e) {
            showAlert("Erreur", "Impossible d'enregistrer la photo : " + e.getMessage(), Alert.AlertType.ERROR);
        }
    }

    @FXML
    void handleRemovePhoto(ActionEvent event) {
        try {
            deleteOldPhotos();
        } catch (IOException ignored) {}

        // Revert to initials
        profilePhotoView.setVisible(false);
        avatarInitialsLabel.setVisible(true);
        avatarCircle.setFill(Color.web("#f1f5f9"));
        removePhotoBtn.setVisible(false);
        removePhotoBtn.setManaged(false);
    }

    // ------------------------------------------------------------------
    //  SAVE PROFILE INFO
    // ------------------------------------------------------------------

    @FXML
    void handleSave(ActionEvent event) {
        String name        = nameField.getText().trim();
        String tel         = telField.getText().trim();
        String oldPass     = oldPassField.getText().trim();
        String newPass     = newPassField.getText().trim();
        String confirmPass = confirmPassField.getText().trim();

        if (name.isEmpty()) {
            showAlert("Erreur", "Le nom complet est obligatoire.", Alert.AlertType.ERROR);
            return;
        }

        try {
            if (!oldPass.isEmpty() || !newPass.isEmpty() || !confirmPass.isEmpty()) {
                if (oldPass.isEmpty()) {
                    showAlert("Erreur", "Veuillez saisir votre mot de passe actuel.", Alert.AlertType.ERROR);
                    return;
                }
                if (newPass.isEmpty()) {
                    showAlert("Erreur", "Veuillez saisir le nouveau mot de passe.", Alert.AlertType.ERROR);
                    return;
                }
                if (!newPass.equals(confirmPass)) {
                    showAlert("Erreur", "Les mots de passe ne correspondent pas.", Alert.AlertType.ERROR);
                    return;
                }
            }

            userService.updateUserProfile(
                currentUser.getId(),
                name,
                tel,
                oldPass.isEmpty() ? null : oldPass,
                newPass.isEmpty() ? null : newPass
            );

            currentUser.setName(name);
            currentUser.setTel(tel);

            showAlert("Succès", "Votre profil a été modifié avec succès !", Alert.AlertType.INFORMATION);

            oldPassField.clear();
            newPassField.clear();
            confirmPassField.clear();

        } catch (Exception ex) {
            showAlert("Erreur de modification", ex.getMessage(), Alert.AlertType.ERROR);
        }
    }

    // ------------------------------------------------------------------
    //  PRIVATE HELPERS
    // ------------------------------------------------------------------

    private void loadUserPhoto() {
        String[] exts = {".jpg", ".jpeg", ".png", ".gif"};
        for (String ext : exts) {
            Path photoPath = Paths.get(PHOTOS_DIR + "user_" + currentUser.getId() + ext);
            if (Files.exists(photoPath)) {
                Image img = new Image(photoPath.toUri().toString(), true);
                showPhoto(img);
                return;
            }
        }
    }

    private void showPhoto(Image img) {
        profilePhotoView.setImage(img);
        profilePhotoView.setVisible(true);
        avatarInitialsLabel.setVisible(false);
        removePhotoBtn.setVisible(true);
        removePhotoBtn.setManaged(true);
    }

    private void deleteOldPhotos() throws IOException {
        String[] exts = {".jpg", ".jpeg", ".png", ".gif"};
        for (String ext : exts) {
            Path p = Paths.get(PHOTOS_DIR + "user_" + currentUser.getId() + ext);
            Files.deleteIfExists(p);
        }
    }

    private String buildInitials(String name) {
        if (name == null || name.isEmpty()) return "?";
        String[] parts = name.split(" ");
        String initials = "";
        if (parts.length > 0 && !parts[0].isEmpty()) initials += parts[0].substring(0, 1).toUpperCase();
        if (parts.length > 1 && !parts[1].isEmpty()) initials += parts[1].substring(0, 1).toUpperCase();
        return initials.isEmpty() ? "?" : initials;
    }

    private void showAlert(String title, String message, Alert.AlertType type) {
        Alert alert = new Alert(type);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(message);
        DialogPane dp = alert.getDialogPane();
        dp.getStylesheets().add(getClass().getResource("/com/userapp/css/styles.css").toExternalForm());
        dp.getStyleClass().add("glass-card");
        alert.showAndWait();
    }
}
