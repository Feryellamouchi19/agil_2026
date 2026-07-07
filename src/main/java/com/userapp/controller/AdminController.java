package com.userapp.controller;

import com.userapp.entite.User;
import com.userapp.entite.Role;
import com.userapp.service.UserService;
import com.userapp.service.EmailService;
import com.userapp.service.LogService;
import com.userapp.tools.UserSession;

import javafx.collections.FXCollections;
import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.input.KeyEvent;
import javafx.scene.layout.FlowPane;
import javafx.scene.layout.GridPane;
import javafx.scene.layout.HBox;
import javafx.scene.layout.VBox;
import javafx.scene.shape.Circle;
import javafx.stage.Modality;
import javafx.stage.Stage;
import javafx.stage.Window;

import java.util.List;
import java.util.stream.Collectors;

public class AdminController {
    private UserService userService;
    private EmailService emailService;
    private User currentUser;

    @FXML
    private TextField searchField;

    @FXML
    private ComboBox<String> roleFilterCombo;

    @FXML
    private Button createBtn;

    @FXML
    private FlowPane cardsContainer;

    public void initData(UserService userService, EmailService emailService) {
        this.userService = userService;
        this.emailService = emailService;
        this.currentUser = UserSession.getInstance().getCurrentUser();

        // Initialize Role filter combo
        roleFilterCombo.getItems().addAll("Tous les rôles", "Administrateur", "Gérant", "Fournisseur", "Client");
        roleFilterCombo.setValue("Tous les rôles");

        refreshCards();
    }

    private void refreshCards() {
        cardsContainer.getChildren().clear();
        String query = searchField.getText() == null ? "" : searchField.getText().trim().toLowerCase();
        String roleFilter = roleFilterCombo.getValue();
        if (roleFilter == null) roleFilter = "Tous les rôles";

        List<User> list = userService.getAllUsers();
        final String finalRole = roleFilter;
        
        List<User> filtered = list.stream().filter(u -> {
            boolean matchesSearch = u.getName().toLowerCase().contains(query) || 
                                    u.getEmail().toLowerCase().contains(query) ||
                                    (u.getTel() != null && u.getTel().contains(query));
            
            boolean matchesRole = finalRole.equals("Tous les rôles") || 
                                  u.getRole().getDisplayName().equalsIgnoreCase(finalRole);
            
            return matchesSearch && matchesRole;
        }).collect(Collectors.toList());

        for (User u : filtered) {
            cardsContainer.getChildren().add(createCard(u));
        }
    }

    @FXML
    void handleSearch(KeyEvent event) {
        refreshCards();
    }

    @FXML
    void handleFilter(ActionEvent event) {
        refreshCards();
    }

    private VBox createCard(User user) {
        VBox card = new VBox(10);
        card.getStyleClass().add("user-card");
        card.setPrefWidth(220);
        card.setPrefHeight(160);
        card.setAlignment(Pos.CENTER);

        // Avatar
        Label avatarLabel = new Label();
        String initials = "";
        String[] parts = user.getName().split(" ");
        if (parts.length > 0 && !parts[0].isEmpty()) initials += parts[0].substring(0, 1).toUpperCase();
        if (parts.length > 1 && !parts[1].isEmpty()) initials += parts[1].substring(0, 1).toUpperCase();
        if (initials.isEmpty()) initials = "U";
        avatarLabel.setText(initials);
        avatarLabel.setStyle("-fx-text-fill: white; -fx-font-weight: bold; -fx-font-size: 20px;");
        
        VBox avatarBox = new VBox(avatarLabel);
        avatarBox.setAlignment(Pos.CENTER);
        avatarBox.setPrefSize(50, 50);
        avatarBox.setMaxSize(50, 50);
        avatarBox.getStyleClass().add("user-card-avatar");
        Circle clip = new Circle(25, 25, 25);
        avatarBox.setClip(clip);

        Label nameLabel = new Label(user.getName());
        nameLabel.getStyleClass().add("text-subtitle");

        Label emailLabel = new Label(user.getEmail());
        emailLabel.getStyleClass().add("text-muted");

        Label roleLabel = new Label(user.getRole().getDisplayName());
        roleLabel.getStyleClass().add("role-badge-text");
        HBox roleBadge = new HBox(roleLabel);
        roleBadge.setAlignment(Pos.CENTER);
        roleBadge.getStyleClass().add("role-badge");

        card.getChildren().addAll(avatarBox, nameLabel, emailLabel, roleBadge);

        // Click action
        card.setOnMouseClicked(e -> showUserDetailsDialog(createBtn.getScene().getWindow(), user));

        return card;
    }

    @FXML
    void handleCreateAccount(ActionEvent event) {
        showCreateUserDialog(createBtn.getScene().getWindow());
    }

    private void showCreateUserDialog(Window owner) {
        Stage dialog = new Stage();
        dialog.initModality(Modality.WINDOW_MODAL);
        dialog.initOwner(owner);
        dialog.setTitle("Créer un nouvel utilisateur");
        dialog.setResizable(false);

        VBox layout = new VBox(15);
        layout.getStyleClass().addAll("glass-card");
        layout.setStyle("-fx-background-color: white;");
        layout.setPadding(new Insets(25));
        layout.setPrefWidth(400);

        Label title = new Label("Nouvel Utilisateur");
        title.getStyleClass().add("text-title");

        GridPane grid = new GridPane();
        grid.setHgap(10);
        grid.setVgap(10);

        Label nameLabel = new Label("Nom complet :");
        nameLabel.getStyleClass().add("text-header");
        TextField nameInput = new TextField();
        grid.add(nameLabel, 0, 0);
        grid.add(nameInput, 0, 1);

        Label emailLabel = new Label("Adresse e-mail :");
        emailLabel.getStyleClass().add("text-header");
        TextField emailInput = new TextField();
        grid.add(emailLabel, 0, 2);
        grid.add(emailInput, 0, 3);

        Label telLabel = new Label("Téléphone :");
        telLabel.getStyleClass().add("text-header");
        TextField telInput = new TextField();
        grid.add(telLabel, 0, 4);
        grid.add(telInput, 0, 5);

        Label roleLabel = new Label("Rôle :");
        roleLabel.getStyleClass().add("text-header");
        ComboBox<String> roleInput = new ComboBox<>(FXCollections.observableArrayList("Administrateur", "Gérant", "Fournisseur", "Client"));
        roleInput.setValue("Client");
        roleInput.setPrefWidth(350);
        grid.add(roleLabel, 0, 6);
        grid.add(roleInput, 0, 7);

        HBox btnContainer = new HBox(10);
        btnContainer.setAlignment(Pos.CENTER_RIGHT);
        btnContainer.setPadding(new Insets(15, 0, 0, 0));

        Button cancelBtn = new Button("Annuler");
        cancelBtn.getStyleClass().add("button-secondary");
        cancelBtn.setOnAction(e -> dialog.close());

        Button saveBtn = new Button("Créer");
        saveBtn.getStyleClass().add("button-primary");
        saveBtn.setOnAction(e -> {
            String name = nameInput.getText().trim();
            String email = emailInput.getText().trim();
            String tel = telInput.getText().trim();
            String roleVal = roleInput.getValue();

            try {
                String plainPassword = UserService.generateRandomPassword();
                Role role = Role.fromString(roleVal);
                
                User newUser = userService.createUser(name, email, plainPassword, role, tel);
                
                LogService.log(currentUser.getName(), currentUser.getEmail(),
                        LogService.Action.CREATION_COMPTE,
                        "Nouveau compte : " + name + " (" + email + ") — Rôle : " + role.getDisplayName());
                dialog.close();
                refreshCards();
                
                emailService.sendCredentialsEmail(owner, newUser, plainPassword);

            } catch (Exception ex) {
                showAlert("Erreur", ex.getMessage(), Alert.AlertType.ERROR);
            }
        });

        btnContainer.getChildren().addAll(cancelBtn, saveBtn);
        layout.getChildren().addAll(title, grid, btnContainer);

        Scene scene = new Scene(layout);
        scene.getStylesheets().add(getClass().getResource("/com/userapp/css/styles.css").toExternalForm());
        dialog.setScene(scene);
        dialog.showAndWait();
    }

    private void showUserDetailsDialog(Window owner, User selectedUser) {
        Stage dialog = new Stage();
        dialog.initModality(Modality.WINDOW_MODAL);
        dialog.initOwner(owner);
        dialog.setTitle("Détails du compte");
        dialog.setResizable(false);

        VBox layout = new VBox(15);
        layout.getStyleClass().addAll("glass-card");
        layout.setStyle("-fx-background-color: white;");
        layout.setPadding(new Insets(25));
        layout.setPrefWidth(400);

        Label title = new Label("Détails du compte");
        title.getStyleClass().add("text-title");

        GridPane grid = new GridPane();
        grid.setHgap(10);
        grid.setVgap(10);

        Label nameLabel = new Label("Nom complet :");
        nameLabel.getStyleClass().add("text-header");
        TextField nameInput = new TextField(selectedUser.getName());
        grid.add(nameLabel, 0, 0);
        grid.add(nameInput, 0, 1);

        Label emailLabel = new Label("Adresse e-mail (Lecture seule):");
        emailLabel.getStyleClass().add("text-header");
        TextField emailInput = new TextField(selectedUser.getEmail());
        emailInput.setEditable(false);
        emailInput.setDisable(true);
        grid.add(emailLabel, 0, 2);
        grid.add(emailInput, 0, 3);

        Label telLabel = new Label("Téléphone :");
        telLabel.getStyleClass().add("text-header");
        TextField telInput = new TextField(selectedUser.getTel() == null ? "" : selectedUser.getTel());
        grid.add(telLabel, 0, 4);
        grid.add(telInput, 0, 5);

        Label roleLabel = new Label("Rôle :");
        roleLabel.getStyleClass().add("text-header");
        ComboBox<String> roleInput = new ComboBox<>(FXCollections.observableArrayList("Administrateur", "Gérant", "Fournisseur", "Client"));
        roleInput.setValue(selectedUser.getRole().getDisplayName());
        roleInput.setPrefWidth(350);
        grid.add(roleLabel, 0, 6);
        grid.add(roleInput, 0, 7);

        // Buttons
        HBox btnContainer = new HBox(10);
        btnContainer.setAlignment(Pos.CENTER_RIGHT);
        btnContainer.setPadding(new Insets(15, 0, 0, 0));

        Button deleteBtn = new Button("Supprimer");
        deleteBtn.getStyleClass().add("button-danger");
        deleteBtn.setOnAction(e -> {
            if (selectedUser.getId() == currentUser.getId()) {
                showAlert("Action impossible", "Vous ne pouvez pas supprimer votre propre compte !", Alert.AlertType.WARNING);
                return;
            }

            Alert confirm = new Alert(Alert.AlertType.CONFIRMATION, "Voulez-vous vraiment supprimer cet utilisateur ?", ButtonType.YES, ButtonType.NO);
            confirm.setTitle("Confirmation de suppression");
            confirm.setHeaderText(null);
            confirm.showAndWait().ifPresent(response -> {
                if (response == ButtonType.YES) {
                    try {
                        userService.deleteUser(selectedUser.getId());
                        LogService.log(currentUser.getName(), currentUser.getEmail(),
                                LogService.Action.SUPPRESSION_COMPTE,
                                "Compte supprimé : " + selectedUser.getName() + " (" + selectedUser.getEmail() + ")");
                        dialog.close();
                        refreshCards();
                        showAlert("Succès", "L'utilisateur a été supprimé.", Alert.AlertType.INFORMATION);
                    } catch (Exception ex) {
                        showAlert("Erreur", ex.getMessage(), Alert.AlertType.ERROR);
                    }
                }
            });
        });

        Button saveBtn = new Button("Enregistrer");
        saveBtn.getStyleClass().add("button-primary");
        saveBtn.setOnAction(e -> {
            try {
                String name = nameInput.getText().trim();
                String tel = telInput.getText().trim();
                Role role = Role.fromString(roleInput.getValue());

                userService.updateUserAsAdmin(selectedUser.getId(), name, tel, role);
                
                LogService.log(currentUser.getName(), currentUser.getEmail(),
                        LogService.Action.MODIFICATION_COMPTE,
                        "Modification : " + name + " (" + selectedUser.getEmail() + ") — Rôle : " + role.getDisplayName());
                dialog.close();
                refreshCards();
                showAlert("Succès", "Les modifications ont été enregistrées.", Alert.AlertType.INFORMATION);
            } catch (Exception ex) {
                showAlert("Erreur de modification", ex.getMessage(), Alert.AlertType.ERROR);
            }
        });

        HBox spacer = new HBox();
        HBox.setHgrow(spacer, javafx.scene.layout.Priority.ALWAYS);

        btnContainer.getChildren().addAll(deleteBtn, spacer, saveBtn);

        layout.getChildren().addAll(title, grid, btnContainer);

        Scene scene = new Scene(layout);
        scene.getStylesheets().add(getClass().getResource("/com/userapp/css/styles.css").toExternalForm());
        dialog.setScene(scene);
        dialog.showAndWait();
    }

    private void showAlert(String title, String message, Alert.AlertType type) {
        Alert alert = new Alert(type);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(message);
        
        DialogPane dialogPane = alert.getDialogPane();
        dialogPane.getStylesheets().add(getClass().getResource("/com/userapp/css/styles.css").toExternalForm());
        dialogPane.getStyleClass().add("glass-card");
        
        alert.showAndWait();
    }
}
