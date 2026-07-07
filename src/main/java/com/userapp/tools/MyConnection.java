package com.userapp.tools;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;

/**
 * Singleton de connexion à la base de données
 */
public class MyConnection {

    private static final String URL = "jdbc:mysql://localhost:3306/agil_2026";
    private static final String LOGIN = "root";
    private static final String PWD = "";

    private static MyConnection instance;
    private static Connection cnx;

    private MyConnection() {
        try {
            Class.forName("com.mysql.cj.jdbc.Driver");
            cnx = DriverManager.getConnection(URL, LOGIN, PWD);
            System.out.println("Connexion à la base de données agil_2026 établie !");
        } catch (ClassNotFoundException e) {
            System.err.println("Pilote JDBC non trouvé : " + e.getMessage());
        } catch (SQLException e) {
            System.err.println("Erreur de connexion initiale : " + e.getMessage());
        }
    }

    public static MyConnection getInstance() {
        if (instance == null) {
            synchronized (MyConnection.class) {
                if (instance == null) {
                    instance = new MyConnection();
                }
            }
        }
        return instance;
    }

    public synchronized Connection getConnection() {
        return getConnectionStatic();
    }

    public static synchronized Connection getConnectionStatic() {
        try {
            if (cnx == null || cnx.isClosed()) {
                cnx = DriverManager.getConnection(URL, LOGIN, PWD);
            }
        } catch (SQLException e) {
            System.err.println("Erreur fatale ouverture connexion : " + e.getMessage());
            cnx = null;
        }
        return cnx;
    }

    public void closeConnection() {
        try {
            if (cnx != null && !cnx.isClosed()) {
                cnx.close();
                System.out.println("Connexion fermée.");
            }
        } catch (SQLException e) {
            System.err.println("Erreur fermeture connexion : " + e.getMessage());
        }
    }
}
