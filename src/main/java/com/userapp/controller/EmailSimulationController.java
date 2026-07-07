package com.userapp.controller;

import javafx.animation.KeyFrame;
import javafx.animation.KeyValue;
import javafx.animation.Timeline;
import javafx.animation.TranslateTransition;
import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.layout.Pane;
import javafx.scene.paint.Color;
import javafx.scene.shape.Circle;
import javafx.scene.shape.Rectangle;
import javafx.stage.Stage;
import javafx.util.Duration;

public class EmailSimulationController {
    private String email;
    private String name;
    private String password;

    @FXML
    private Label titleLabel;

    @FXML
    private Pane animPane;

    @FXML
    private Rectangle serverNode;

    @FXML
    private Circle serverLed;

    @FXML
    private Rectangle inboxNode;

    @FXML
    private Pane envelope;

    @FXML
    private Label statusLabel;

    @FXML
    private Button closeBtn;

    public void initData(String email, String name, String password) {
        this.email = email;
        this.name = name;
        this.password = password;

        titleLabel.setText("Envoi des coordonnées à " + name);
        statusLabel.setText("Préparation du message...");
        
        // Start animation lifecycle
        startAnimationFlow();
    }

    private void startAnimationFlow() {
        // 1. LED Blinking Timeline
        Timeline ledBlinker = new Timeline(
            new KeyFrame(Duration.ZERO, e -> serverLed.setFill(Color.web("#22c55e"))),
            new KeyFrame(Duration.seconds(0.3), e -> serverLed.setFill(Color.web("#15803d"))),
            new KeyFrame(Duration.seconds(0.6), e -> {})
        );
        ledBlinker.setCycleCount(Timeline.INDEFINITE);
        ledBlinker.play();

        // 2. Status Label updates Timeline
        Timeline statusUpdater = new Timeline(
            new KeyFrame(Duration.ZERO, e -> statusLabel.setText("Connexion au serveur de messagerie...")),
            new KeyFrame(Duration.seconds(0.6), e -> statusLabel.setText("Établissement du canal sécurisé (TLS)...")),
            new KeyFrame(Duration.seconds(1.2), e -> statusLabel.setText("Envoi des identifiants à " + email + "...")),
            new KeyFrame(Duration.seconds(2.0), e -> statusLabel.setText("Finalisation de la transmission..."))
        );
        statusUpdater.play();

        // 3. Envelope fly Transition
        TranslateTransition flyTransition = new TranslateTransition(Duration.seconds(2.2), envelope);
        // Start layout is 105, target coordinate relative is:
        // inboxNode is at x=375. Envelope is at local layoutX=105.
        // We translate by (375 - 105 - 32) = 238px to land exactly on the inbox entrance.
        flyTransition.setFromX(0);
        flyTransition.setToX(238);
        
        flyTransition.setOnFinished(e -> {
            ledBlinker.stop();
            serverLed.setFill(Color.web("#15803d")); // Keep steady dark green
            
            // Turn Inbox Node Green (success)
            inboxNode.setFill(Color.web("#22c55e"));
            
            // Update status text
            statusLabel.setText("E-mail envoyé avec succès ! (Enregistré dans le log)");
            statusLabel.setStyle("-fx-text-fill: #22c55e; -fx-font-weight: bold;");
            
            // Enable closing
            closeBtn.setDisable(false);
        });

        // Start flight after a small delay
        Timeline delay = new Timeline(new KeyFrame(Duration.seconds(0.8), e -> flyTransition.play()));
        delay.play();
    }

    @FXML
    void handleClose(ActionEvent event) {
        Stage stage = (Stage) closeBtn.getScene().getWindow();
        stage.close();
    }
}
