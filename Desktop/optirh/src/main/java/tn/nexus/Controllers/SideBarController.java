package tn.nexus.Controllers;

import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.layout.AnchorPane;
import tn.nexus.Entities.User;

public class SideBarController {
    tn.nexus.Entities.User User;
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

            if(User.getRole().equals("Administrateur") || User.getRole().equals("Chef_Projet")){
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Mission/Projet.fxml"));
            root = loader.load();}

            else {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/Mission/MissionEmploye.fxml"));
                root = loader.load();
            }
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
