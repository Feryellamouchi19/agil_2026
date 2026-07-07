module com.userapp {
    requires javafx.controls;
    requires javafx.fxml;
    requires java.base;
    requires java.sql;
    requires java.net.http;
    requires jdk.httpserver;
    requires java.desktop;
    requires com.google.gson;

    // Opens packages for FXML loading and reflection access
    opens com.userapp to javafx.fxml;
    opens com.userapp.controller to javafx.fxml;
    opens com.userapp.entite to javafx.base;

    // Exports packages
    exports com.userapp;
    exports com.userapp.entite;
    exports com.userapp.controller;
    exports com.userapp.service;
}
