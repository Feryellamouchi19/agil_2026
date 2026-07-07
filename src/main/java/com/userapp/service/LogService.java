package com.userapp.service;

import java.io.*;
import java.nio.file.*;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.Collections;
import java.util.List;

/**
 * Service de journalisation des activités.
 * Chaque action est enregistrée dans un fichier texte local (activity_log.txt).
 */
public class LogService {

    private static final String LOG_FILE = "activity_log.txt";
    private static final DateTimeFormatter FORMATTER = DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm:ss");

    // Types d'actions
    public enum Action {
        CONNEXION       ("🔐 Connexion"),
        DECONNEXION     ("🔓 Déconnexion"),
        CREATION_COMPTE ("✅ Création de compte"),
        SUPPRESSION_COMPTE("🗑️ Suppression de compte"),
        MODIFICATION_COMPTE("✏️ Modification de compte"),
        MODIFICATION_PROFIL("👤 Modification de profil"),
        CONNEXION_GOOGLE("🌐 Connexion via Google");

        private final String label;
        Action(String label) { this.label = label; }
        public String getLabel() { return label; }
    }

    /** Enregistre une nouvelle entrée dans le journal. */
    public static void log(String actorName, String actorEmail, Action action, String details) {
        String timestamp = LocalDateTime.now().format(FORMATTER);
        String line = timestamp + " | " + action.getLabel() + " | " + actorName + " (" + actorEmail + ") | " + details;

        try {
            Files.writeString(
                Path.of(LOG_FILE),
                line + System.lineSeparator(),
                StandardOpenOption.CREATE,
                StandardOpenOption.APPEND
            );
        } catch (IOException e) {
            System.err.println("[LogService] Erreur d'écriture : " + e.getMessage());
        }
    }

    /** Retourne toutes les entrées du journal (la plus récente en premier). */
    public static List<LogEntry> loadAll() {
        List<LogEntry> entries = new ArrayList<>();
        File file = new File(LOG_FILE);
        if (!file.exists()) return entries;

        try (BufferedReader reader = new BufferedReader(new FileReader(file))) {
            String line;
            while ((line = reader.readLine()) != null) {
                if (line.trim().isEmpty()) continue;
                LogEntry entry = parse(line);
                if (entry != null) entries.add(entry);
            }
        } catch (IOException e) {
            System.err.println("[LogService] Erreur de lecture : " + e.getMessage());
        }

        // Most recent first
        Collections.reverse(entries);
        return entries;
    }

    private static LogEntry parse(String line) {
        String[] parts = line.split(" \\| ", 4);
        if (parts.length < 4) return null;
        return new LogEntry(parts[0].trim(), parts[1].trim(), parts[2].trim(), parts[3].trim());
    }

    // ------------------------------------------------------------------
    //  Data Class
    // ------------------------------------------------------------------
    public static class LogEntry {
        private final String timestamp;
        private final String action;
        private final String actor;
        private final String details;

        public LogEntry(String timestamp, String action, String actor, String details) {
            this.timestamp = timestamp;
            this.action    = action;
            this.actor     = actor;
            this.details   = details;
        }

        public String getTimestamp() { return timestamp; }
        public String getAction()    { return action; }
        public String getActor()     { return actor; }
        public String getDetails()   { return details; }
    }
}
