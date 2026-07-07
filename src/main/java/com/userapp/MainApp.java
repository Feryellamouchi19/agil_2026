package com.userapp;

import com.userapp.tools.MyConnection;
import javafx.application.Application;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.stage.Stage;
import javafx.scene.control.Alert;

import java.sql.Connection;

public class MainApp extends Application {

    @Override
    public void start(Stage stage) throws Exception {
        // Initialiser la connexion au démarrage pour vérifier que ça marche
        Connection cnx = MyConnection.getInstance().getConnection();
        if (cnx == null) {
            Alert alert = new Alert(Alert.AlertType.ERROR);
            alert.setTitle("Erreur de connexion");
            alert.setHeaderText("Impossible de se connecter à la base de données agil_2026");
            alert.setContentText(
                "Vérifiez que MySQL est démarré et que la base agil_2026 existe.\n" +
                "Vérifiez les paramètres dans MyConnection.java."
            );
            alert.showAndWait();
            return;
        }

        FXMLLoader loader = new FXMLLoader(getClass().getResource("/com/userapp/view/login.fxml"));
        Parent root = loader.load();

        stage.setTitle("Connexion - Gestion d'Utilisateurs");
        Scene scene = new Scene(root);
        scene.getStylesheets().add(getClass().getResource("/com/userapp/css/styles.css").toExternalForm());

        stage.setScene(scene);
        stage.setResizable(false);
        stage.show();
    }

    @Override
    public void stop() throws Exception {
        // Fermer la connexion JDBC proprement à la fermeture de l'application
        MyConnection.getInstance().closeConnection();
        super.stop();
    }

    public static void main(String[] args) {
        launch(args);
    }
}
