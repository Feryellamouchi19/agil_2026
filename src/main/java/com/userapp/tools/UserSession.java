package com.userapp.tools;

import com.userapp.entite.User;

/**
 * Singleton pour gérer la session utilisateur globalement dans toute l'application.
 */
public final class UserSession {

    private static UserSession instance;
    private User currentUser;

    private UserSession() {
    }

    public static UserSession getInstance() {
        if (instance == null) {
            instance = new UserSession();
        }
        return instance;
    }

    public User getCurrentUser() {
        return currentUser;
    }

    public void setCurrentUser(User currentUser) {
        this.currentUser = currentUser;
    }

    public void cleanUserSession() {
        currentUser = null;
        instance = null; // Optionnel, mais permet de vider l'instance
    }
}
