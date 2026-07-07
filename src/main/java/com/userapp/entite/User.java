package com.userapp.entite;

public class User {
    private int id;
    private String name; // mapped to 'nom'
    private String email;
    private String passwordHash; // mapped to 'mot de passe'
    private String tel; // mapped to 'tel'
    private Role role;

    // Constructeur pour un nouvel utilisateur (l'ID sera généré par MySQL)
    public User(String name, String email, String passwordHash, Role role, String tel) {
        this.id = 0;
        this.name = name;
        this.email = email;
        this.passwordHash = passwordHash;
        this.role = role;
        this.tel = tel;
    }

    // Constructeur pour un utilisateur lu depuis la base de données
    public User(int id, String name, String email, String passwordHash, Role role, String tel) {
        this.id = id;
        this.name = name;
        this.email = email;
        this.passwordHash = passwordHash;
        this.role = role;
        this.tel = tel;
    }

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    
    public String getName() { return name; }
    public void setName(String name) { this.name = name; }
    
    public String getEmail() { return email; }
    public void setEmail(String email) { this.email = email; }
    
    public String getPasswordHash() { return passwordHash; }
    public void setPasswordHash(String passwordHash) { this.passwordHash = passwordHash; }
    
    public Role getRole() { return role; }
    public void setRole(Role role) { this.role = role; }
    
    public String getRoleDisplayName() { return role.getDisplayName(); }
    
    public String getTel() { return tel; }
    public void setTel(String tel) { this.tel = tel; }
}
