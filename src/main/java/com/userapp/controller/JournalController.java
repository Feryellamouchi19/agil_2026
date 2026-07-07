package com.userapp.controller;

import com.userapp.service.LogService;
import com.userapp.service.LogService.LogEntry;

import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.control.*;
import javafx.scene.layout.*;
import javafx.scene.paint.Color;
import javafx.scene.shape.Circle;

import java.util.List;
import java.util.stream.Collectors;

public class JournalController {

    @FXML private TextField searchField;
    @FXML private ComboBox<String> filterCombo;
    @FXML private Button  refreshBtn;
    @FXML private Label   totalLabel;
    @FXML private VBox    logContainer;

    @FXML
    public void initialize() {
        filterCombo.getItems().addAll("Toutes les actions", "Connexion", "Déconnexion",
                "Création", "Suppression", "Modification", "Google");
        filterCombo.setValue("Toutes les actions");
        refreshLogs();
    }

    @FXML
    void handleRefresh(ActionEvent event) { refreshLogs(); }

    @FXML
    void handleSearch(javafx.scene.input.KeyEvent event) { refreshLogs(); }

    @FXML
    void handleFilter(ActionEvent event) { refreshLogs(); }

    private void refreshLogs() {
        List<LogEntry> all = LogService.loadAll();
        String query  = searchField.getText() == null ? "" : searchField.getText().trim().toLowerCase();
        String filter = filterCombo.getValue();

        List<LogEntry> filtered = all.stream().filter(e -> {
            boolean matchSearch = query.isEmpty()
                    || e.getActor().toLowerCase().contains(query)
                    || e.getAction().toLowerCase().contains(query)
                    || e.getDetails().toLowerCase().contains(query);

            boolean matchFilter = filter == null || filter.equals("Toutes les actions")
                    || e.getAction().toLowerCase().contains(filter.toLowerCase());

            return matchSearch && matchFilter;
        }).collect(Collectors.toList());

        totalLabel.setText(filtered.size() + " entrée(s) trouvée(s)");

        logContainer.getChildren().clear();
        if (filtered.isEmpty()) {
            Label empty = new Label("Aucune activité enregistrée pour le moment.");
            empty.getStyleClass().add("text-muted");
            empty.setStyle("-fx-padding: 40; -fx-font-size: 14px;");
            logContainer.getChildren().add(empty);
            return;
        }

        for (LogEntry entry : filtered) {
            logContainer.getChildren().add(buildLogCard(entry));
        }
    }

    private HBox buildLogCard(LogEntry entry) {
        HBox card = new HBox(15);
        card.setAlignment(Pos.CENTER_LEFT);
        card.setStyle("-fx-background-color: white; -fx-background-radius: 10; " +
                "-fx-border-color: #e2e8f0; -fx-border-radius: 10; -fx-border-width: 1; " +
                "-fx-padding: 14 18 14 18; -fx-cursor: default;");
        card.setPrefWidth(Double.MAX_VALUE);

        // Colored dot based on action type
        Circle dot = new Circle(6);
        dot.setFill(getActionColor(entry.getAction()));

        // Action icon + text
        VBox infoBox = new VBox(3);
        HBox.setHgrow(infoBox, Priority.ALWAYS);

        Label actionLabel = new Label(entry.getAction());
        actionLabel.setStyle("-fx-font-weight: bold; -fx-font-size: 13px; -fx-text-fill: #1e293b;");

        HBox metaRow = new HBox(10);
        Label actorLabel = new Label("👤 " + entry.getActor());
        actorLabel.setStyle("-fx-font-size: 12px; -fx-text-fill: #64748b;");

        Label detailsLabel = new Label("— " + entry.getDetails());
        detailsLabel.setStyle("-fx-font-size: 12px; -fx-text-fill: #94a3b8;");

        metaRow.getChildren().addAll(actorLabel, detailsLabel);
        infoBox.getChildren().addAll(actionLabel, metaRow);

        // Timestamp on the right
        Label timeLabel = new Label("🕐 " + entry.getTimestamp());
        timeLabel.setStyle("-fx-font-size: 11px; -fx-text-fill: #94a3b8;");
        timeLabel.setMinWidth(130);
        timeLabel.setAlignment(Pos.CENTER_RIGHT);

        card.getChildren().addAll(dot, infoBox, timeLabel);
        return card;
    }

    private Color getActionColor(String action) {
        if (action.contains("Connexion") || action.contains("Google")) return Color.web("#22c55e");
        if (action.contains("Déconnexion"))                             return Color.web("#94a3b8");
        if (action.contains("Création"))                                return Color.web("#eab308");
        if (action.contains("Suppression"))                             return Color.web("#ef4444");
        if (action.contains("Modification"))                            return Color.web("#e11d48");
        return Color.web("#6366f1");
    }
}
