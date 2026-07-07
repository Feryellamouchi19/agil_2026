package com.userapp.service;

import com.userapp.entite.Role;
import com.userapp.entite.User;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

/**
 * JDBC-based data access layer for the 'users' table.
 */
public class DatabaseService {

    public DatabaseService() {
    }

    // ------------------------------------------------------------------
    //  CREATE
    // ------------------------------------------------------------------

    public void insertUser(User user) throws SQLException {
        Connection conn = com.userapp.tools.MyConnection.getInstance().getConnection();
        
        // Calculer le prochain ID (MAX(id) + 1)
        int nextId = 1;
        try (Statement stmt = conn.createStatement();
             ResultSet rs = stmt.executeQuery("SELECT MAX(id) FROM users")) {
            if (rs.next()) {
                nextId = rs.getInt(1) + 1;
            }
        }
        
        user.setId(nextId);

        String sql = """
            INSERT INTO users (id, nom, email, `mot de passe`, tel, role)
            VALUES (?, ?, ?, ?, ?, ?)
        """;

        try (PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1, user.getId());
            ps.setString(2, user.getName());
            ps.setString(3, user.getEmail());
            ps.setString(4, user.getPasswordHash());
            ps.setString(5, user.getTel());
            ps.setString(6, user.getRole().name());
            ps.executeUpdate();
        }
    }

    // ------------------------------------------------------------------
    //  READ
    // ------------------------------------------------------------------

    public List<User> loadAllUsers() {
        List<User> users = new ArrayList<>();
        String sql = "SELECT id, nom, email, `mot de passe`, tel, role FROM users";

        try (Statement stmt = com.userapp.tools.MyConnection.getInstance().getConnection().createStatement();
             ResultSet rs   = stmt.executeQuery(sql)) {

            while (rs.next()) {
                users.add(mapRow(rs));
            }
        } catch (SQLException e) {
            System.err.println("[DatabaseService] Error loading users: " + e.getMessage());
        }
        return users;
    }

    public User findByEmailAndPassword(String email, String passwordHash) {
        String sql = """
            SELECT id, nom, email, `mot de passe`, tel, role
            FROM users
            WHERE LOWER(email) = LOWER(?) AND `mot de passe` = ?
            LIMIT 1
        """;

        try (PreparedStatement ps = com.userapp.tools.MyConnection.getInstance().getConnection().prepareStatement(sql)) {
            ps.setString(1, email.trim());
            ps.setString(2, passwordHash);

            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return mapRow(rs);
                }
            }
        } catch (SQLException e) {
            System.err.println("[DatabaseService] Error authenticating user: " + e.getMessage());
        }
        return null;
    }

    public boolean emailExists(String email) {
        String sql = "SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(?)";

        try (PreparedStatement ps = com.userapp.tools.MyConnection.getInstance().getConnection().prepareStatement(sql)) {
            ps.setString(1, email.trim());
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return rs.getInt(1) > 0;
                }
            }
        } catch (SQLException e) {
            System.err.println("[DatabaseService] Error checking email: " + e.getMessage());
        }
        return false;
    }

    public int countUsers() {
        String sql = "SELECT COUNT(*) FROM users";
        try (Statement stmt = com.userapp.tools.MyConnection.getInstance().getConnection().createStatement();
             ResultSet rs   = stmt.executeQuery(sql)) {
            if (rs.next()) return rs.getInt(1);
        } catch (SQLException e) {
            System.err.println("[DatabaseService] Error counting users: " + e.getMessage());
        }
        return 0;
    }

    // ------------------------------------------------------------------
    //  UPDATE
    // ------------------------------------------------------------------

    public void updateName(int id, String newName) throws SQLException {
        String sql = "UPDATE users SET nom = ? WHERE id = ?";
        try (PreparedStatement ps = com.userapp.tools.MyConnection.getInstance().getConnection().prepareStatement(sql)) {
            ps.setString(1, newName.trim());
            ps.setInt(2, id);
            ps.executeUpdate();
        }
    }

    public void updatePassword(int id, String passwordHash) throws SQLException {
        String sql = "UPDATE users SET `mot de passe` = ? WHERE id = ?";
        try (PreparedStatement ps = com.userapp.tools.MyConnection.getInstance().getConnection().prepareStatement(sql)) {
            ps.setString(1, passwordHash);
            ps.setInt(2, id);
            ps.executeUpdate();
        }
    }

    public void updateTel(int id, String newTel) throws SQLException {
        String sql = "UPDATE users SET tel = ? WHERE id = ?";
        try (PreparedStatement ps = com.userapp.tools.MyConnection.getInstance().getConnection().prepareStatement(sql)) {
            ps.setString(1, newTel.trim());
            ps.setInt(2, id);
            ps.executeUpdate();
        }
    }

    public void updateUserAsAdmin(int id, String name, String tel, Role role) throws SQLException {
        String sql = "UPDATE users SET nom = ?, tel = ?, role = ? WHERE id = ?";
        try (PreparedStatement ps = com.userapp.tools.MyConnection.getInstance().getConnection().prepareStatement(sql)) {
            ps.setString(1, name.trim());
            ps.setString(2, tel.trim());
            ps.setString(3, role.name());
            ps.setInt(4, id);
            ps.executeUpdate();
        }
    }

    // ------------------------------------------------------------------
    //  DELETE
    // ------------------------------------------------------------------

    public void deleteUser(int id) throws SQLException {
        String sql = "DELETE FROM users WHERE id = ?";
        try (PreparedStatement ps = com.userapp.tools.MyConnection.getInstance().getConnection().prepareStatement(sql)) {
            ps.setInt(1, id);
            ps.executeUpdate();
        }
    }

    // ------------------------------------------------------------------
    //  PRIVATE HELPERS
    // ------------------------------------------------------------------

    private User mapRow(ResultSet rs) throws SQLException {
        return new User(
            rs.getInt("id"),
            rs.getString("nom"),
            rs.getString("email"),
            rs.getString("mot de passe"),
            Role.fromString(rs.getString("role")),
            rs.getString("tel")
        );
    }
}
