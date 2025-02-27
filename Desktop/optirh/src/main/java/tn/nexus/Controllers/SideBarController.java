package tn.nexus.Controllers;

import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.layout.AnchorPane;

public class SideBarController {

    @FXML
    private AnchorPane menu;

    @FXML
    void redirectToManageUsers() {
        Parent root = null;
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Users/ListUsers.fxml"));
            root = loader.load();
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
        menu.getScene().setRoot(root);
    }
    @FXML
    void redirectToProjet() {
        Parent root = null;
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Mission/Projet.fxml"));
            root = loader.load();
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
        menu.getScene().setRoot(root);
    }
    @FXML
    void redirectToReclamation() {
        Parent root = null;
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/reclamation/listeReclamation.fxml"));
            root = loader.load();
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
        menu.getScene().setRoot(root);
    }
    @FXML
    void redirectToMissions() {
        Parent root = null;
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Mission/Projet.fxml"));
            root = loader.load();
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
        menu.getScene().setRoot(root);
    }
    @FXML
    void redirectToEvenement() {
        Parent root = null;
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Evenement/listeEvenemnt.fxml"));
            root = loader.load();
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
        menu.getScene().setRoot(root);
    }
    @FXML
    void redirectToTransport() {
        Parent root = null;
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/transport/GestionTrajet.fxml"));
            root = loader.load();
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
        menu.getScene().setRoot(root);
    }
}
