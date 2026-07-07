package com.userapp.controller;

import com.userapp.entite.User;
import com.userapp.entite.Role;
import com.userapp.tools.UserSession;
import com.userapp.service.UserService;
import com.userapp.service.EmailService;
import com.userapp.service.LogService;

import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.layout.HBox;
import javafx.scene.layout.StackPane;
import javafx.scene.layout.VBox;
import javafx.stage.Stage;

import java.io.IOException;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;

public class DashboardController {
    private UserService userService;
    private EmailService emailService;
    private User currentUser;

    @FXML
    private Label avatarLabel;

    @FXML
    private javafx.scene.image.ImageView sidebarPhotoView;

    @FXML
    private Label nameLabel;

    @FXML
    private HBox badgeContainer;

    @FXML
    private Label roleBadgeLabel;

    @FXML
    private VBox navMenu;

    @FXML
    private Button primaryMenuBtn;

    @FXML
    private Button profileMenuBtn;

    @FXML
    private Button logoutBtn;

    @FXML
    private Label dashboardTitle;

    @FXML
    private Label dateLabel;

    @FXML
    private StackPane contentArea;

    public void initData(UserService userService, EmailService emailService) {
        this.userService = userService;
        this.emailService = emailService;
        this.currentUser = UserSession.getInstance().getCurrentUser();

        nameLabel.setText(currentUser.getName());
        roleBadgeLabel.setText(currentUser.getRole().getDisplayName());
        
        String initials = "";
        String[] parts = currentUser.getName().split(" ");
        if (parts.length > 0 && !parts[0].isEmpty()) initials += parts[0].substring(0, 1).toUpperCase();
        if (parts.length > 1 && !parts[1].isEmpty()) initials += parts[1].substring(0, 1).toUpperCase();
        if (initials.isEmpty()) initials = "U";
        avatarLabel.setText(initials);

        // Load sidebar photo if exists
        loadSidebarPhoto();

        String today = LocalDate.now().format(DateTimeFormatter.ofPattern("EEEE dd MMMM yyyy", java.util.Locale.FRENCH));
        if (today.length() > 0) {
            today = today.substring(0, 1).toUpperCase() + today.substring(1);
        }
        dateLabel.setText(today);

        // Log the login event
        LogService.log(currentUser.getName(), currentUser.getEmail(),
                LogService.Action.CONNEXION, "Connexion réussie");

        // Use Platform.runLater to ensure scene is ready before updating UI
        javafx.application.Platform.runLater(() -> {
            if (currentUser.getRole() == Role.ADMIN) {
                primaryMenuBtn.setVisible(true);
                primaryMenuBtn.setManaged(true);
                primaryMenuBtn.setText("Gestion Utilisateurs");
                dashboardTitle.setText("GESTION DES UTILISATEURS");
                setMenuButtonActive(primaryMenuBtn);
                loadPrimaryPanel();
            } else if (currentUser.getRole() == Role.GERANT) {
                primaryMenuBtn.setVisible(true);
                primaryMenuBtn.setManaged(true);
                primaryMenuBtn.setText("Journal d'Activité");
                dashboardTitle.setText("JOURNAL D'ACTIVITÉ");
                setMenuButtonActive(primaryMenuBtn);
                loadPrimaryPanel();
            } else {
                primaryMenuBtn.setVisible(false);
                primaryMenuBtn.setManaged(false);
                loadProfilePanel();
                setMenuButtonActive(profileMenuBtn);
            }
        });
    }

    @FXML
    void handlePrimaryMenu(ActionEvent event) {
        setMenuButtonActive(primaryMenuBtn);
        loadPrimaryPanel();
    }

    @FXML
    void handleProfileMenu(ActionEvent event) {
        setMenuButtonActive(profileMenuBtn);
        loadProfilePanel();
    }

    @FXML
    void handleLogout(ActionEvent event) {
        LogService.log(currentUser.getName(), currentUser.getEmail(),
                LogService.Action.DECONNEXION, "Déconnexion de l'application");
        UserSession.getInstance().cleanUserSession();
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/com/userapp/view/login.fxml"));
            Parent root = loader.load();
            
            Stage stage = new Stage();
            stage.setTitle("Connexion - Gestion d'Utilisateurs");
            
            Scene scene = new Scene(root);
            scene.getStylesheets().add(getClass().getResource("/com/userapp/css/styles.css").toExternalForm());
            
            stage.setScene(scene);
            stage.show();
            
            ((Stage) logoutBtn.getScene().getWindow()).close();
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    private void setMenuButtonActive(Button activeBtn) {
        primaryMenuBtn.getStyleClass().clear();
        profileMenuBtn.getStyleClass().clear();
        
        primaryMenuBtn.getStyleClass().add("button-sidebar");
        profileMenuBtn.getStyleClass().add("button-sidebar");
        
        activeBtn.getStyleClass().clear();
        activeBtn.getStyleClass().add("button-sidebar-active");
    }

    private void loadPrimaryPanel() {
        try {
            if (currentUser.getRole() == Role.ADMIN) {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/com/userapp/view/admin.fxml"));
                Parent view = loader.load();
                AdminController c = loader.getController();
                c.initData(userService, emailService);
                contentArea.getChildren().setAll(view);
                dashboardTitle.setText("GESTION DES UTILISATEURS");

            } else if (currentUser.getRole() == Role.GERANT) {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/com/userapp/view/journal.fxml"));
                Parent view = loader.load();
                contentArea.getChildren().setAll(view);
                dashboardTitle.setText("JOURNAL D'ACTIVITÉ");
            }
        } catch (IOException e) {
            System.err.println("Error loading primary view: " + e.getMessage());
            e.printStackTrace();
        }
    }

    private void loadProfilePanel() {
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/com/userapp/view/user_profile.fxml"));
            Parent view = loader.load();
            
            UserProfileController controller = loader.getController();
            controller.initData(userService);
            
            contentArea.getChildren().setAll(view);
            dashboardTitle.setText("MON PROFIL");
        } catch (IOException e) {
            System.err.println("Error loading profile view: " + e.getMessage());
            e.printStackTrace();
        }
    }

    private void loadSidebarPhoto() {
        if (sidebarPhotoView == null) return;
        String[] exts = {".jpg", ".jpeg", ".png", ".gif"};
        String baseDir = "src/main/resources/com/userapp/photos/";
        for (String ext : exts) {
            java.io.File f = new java.io.File(baseDir + "user_" + currentUser.getId() + ext);
            if (f.exists()) {
                javafx.scene.image.Image img = new javafx.scene.image.Image(f.toURI().toString(), true);
                sidebarPhotoView.setImage(img);
                sidebarPhotoView.setVisible(true);
                avatarLabel.setVisible(false);
                return;
            }
        }
    }
}
