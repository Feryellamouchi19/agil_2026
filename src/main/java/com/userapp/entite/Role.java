package com.userapp.entite;

public enum Role {
    ADMIN("Administrateur"),
    GERANT("Gérant"),
    FOURNISSEUR("Fournisseur"),
    CLIENT("Client");

    private final String displayName;

    Role(String displayName) {
        this.displayName = displayName;
    }

    public String getDisplayName() {
        return displayName;
    }

    public static Role fromString(String roleStr) {
        for (Role role : Role.values()) {
            if (role.name().equalsIgnoreCase(roleStr) || role.getDisplayName().equalsIgnoreCase(roleStr)) {
                return role;
            }
        }
        return CLIENT; // default fallback
    }
}
