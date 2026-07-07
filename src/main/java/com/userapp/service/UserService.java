package com.userapp.service;

import com.userapp.entite.Role;
import com.userapp.entite.User;

import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.security.SecureRandom;
import java.sql.SQLException;
import java.util.List;

/**
 * Business-logic layer for user management.
 * Delegates all persistence to DatabaseService (JDBC / MySQL).
 */
public class UserService {

    private final DatabaseService dbService;

    public UserService() {
        this.dbService = new DatabaseService();
    }

    // ------------------------------------------------------------------
    //  Read
    // ------------------------------------------------------------------

    public List<User> getAllUsers() {
        return dbService.loadAllUsers();
    }

    // ------------------------------------------------------------------
    //  Authentication
    // ------------------------------------------------------------------

    public User authenticate(String email, String password) {
        if (email == null || password == null) return null;
        return dbService.findByEmailAndPassword(email.trim(), hashPassword(password));
    }

    // ------------------------------------------------------------------
    //  Create
    // ------------------------------------------------------------------

    public User createUser(String name, String email, String plainPassword, Role role, String tel) throws Exception {
        if (name == null || name.trim().isEmpty()) {
            throw new Exception("Le nom est obligatoire.");
        }
        if (email == null || email.trim().isEmpty()) {
            throw new Exception("L'adresse email est obligatoire.");
        }
        if (!email.contains("@") || !email.contains(".")) {
            throw new Exception("L'adresse email est invalide.");
        }
        if (dbService.emailExists(email)) {
            throw new Exception("Un utilisateur avec cette adresse email existe déjà.");
        }

        User newUser = new User(name.trim(), email.trim().toLowerCase(), hashPassword(plainPassword), role, tel);
        try {
            dbService.insertUser(newUser);
        } catch (SQLException e) {
            throw new Exception("Erreur base de données : " + e.getMessage(), e);
        }
        return newUser;
    }

    // ------------------------------------------------------------------
    //  Update – profile
    // ------------------------------------------------------------------

    public boolean updateUserProfile(int id, String newName, String newTel, String oldPassword, String newPassword) throws Exception {
        List<User> users = dbService.loadAllUsers();
        User target = users.stream().filter(u -> u.getId() == id).findFirst().orElse(null);
        if (target == null) throw new Exception("Utilisateur introuvable.");

        try {
            if (newName != null && !newName.trim().isEmpty()) {
                dbService.updateName(id, newName.trim());
            }
            if (newTel != null && !newTel.trim().isEmpty()) {
                dbService.updateTel(id, newTel.trim());
            }

            if (oldPassword != null && !oldPassword.isEmpty()
                    && newPassword != null && !newPassword.isEmpty()) {

                if (!target.getPasswordHash().equals(hashPassword(oldPassword))) {
                    throw new Exception("L'ancien mot de passe est incorrect.");
                }
                if (newPassword.length() < 4 || newPassword.length() > 10) {
                    throw new Exception("Le nouveau mot de passe doit être compris entre 4 et 10 caractères.");
                }
                dbService.updatePassword(id, hashPassword(newPassword));
            }
        } catch (SQLException e) {
            throw new Exception("Erreur base de données : " + e.getMessage(), e);
        }
        return true;
    }

    // ------------------------------------------------------------------
    //  Delete
    // ------------------------------------------------------------------

    public void updateUserAsAdmin(int id, String name, String tel, Role role) throws Exception {
        if (name == null || name.trim().isEmpty()) {
            throw new Exception("Le nom est obligatoire.");
        }
        dbService.updateUserAsAdmin(id, name, tel, role);
    }

    public void deleteUser(int id) {
        try {
            dbService.deleteUser(id);
        } catch (SQLException e) {
            System.err.println("[UserService] Error deleting user: " + e.getMessage());
        }
    }

    // ------------------------------------------------------------------
    //  Utilities
    // ------------------------------------------------------------------

    public static String hashPassword(String password) {
        // Enlève le hachage SHA-256 pour garder le mot de passe en clair (max 10 caractères)
        return password;
    }

    public static String generateRandomPassword() {
        // La colonne 'mot de passe' est de type INT, on ne doit générer que des chiffres
        String chars = "123456789"; // Eviter le 0 au début pour un INT
        SecureRandom random = new SecureRandom();
        StringBuilder sb = new StringBuilder();
        // Generate an 8-character numeric password
        for (int i = 0; i < 8; i++) {
            if(i > 0) chars = "0123456789"; // Autoriser le 0 après le premier chiffre
            sb.append(chars.charAt(random.nextInt(chars.length())));
        }
        return sb.toString();
    }
}
