package tn.nexus.Controllers;

import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.control.Button;
import javafx.scene.layout.AnchorPane;
import tn.nexus.Services.Auth.UserSession;
import tn.nexus.Entities.User;
import tn.nexus.Utils.Enums.Role;

import static tn.nexus.Utils.Enums.Role.Administrateur;
import static tn.nexus.Utils.Enums.Role.Chef_Projet;

public class SideBarController {


    @FXML
    private AnchorPane menu;
     private UserSession userSession= UserSession.getInstance() ;
    @FXML
    private Button logoutButton;
    
    @FXML
    void logout() {
        UserSession.getInstance().logout();
        Parent root = null;
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Auth/Login.fxml"));
            root = loader.load();
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
        logoutButton.getScene().setRoot(root);
    }

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

            if( userSession.hasRole(Role.Administrateur) ||userSession.hasRole(Role.Chef_Projet)){
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
            if( userSession.hasRole(Role.Administrateur) ||userSession.hasRole(Role.Chef_Projet)) {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/reclamation/listeReclamation.fxml"));
            root = loader.load();}
            else
            {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/reclamation/listeReclamationfront.fxml"));
                root = loader.load();}
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
        menu.getScene().setRoot(root);
    }



    @FXML
    void redirectToEvenement() {
        Parent root = null;
        try {
            if( userSession.hasRole(Role.Administrateur) ||userSession.hasRole(Role.Chef_Projet) ) {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/Evenement/listeEvenemnt.fxml"));
                root = loader.load();
            }
            else
            {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Evenement/EventFront.fxml"));
            root = loader.load(); }
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
        menu.getScene().setRoot(root);
    }
    @FXML
    void redirectToTransport() {
        Parent root = null;
        try {
            if (userSession.hasRole(Role.Administrateur) ){
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/transport/GestionTrajet.fxml"));
            root = loader.load();}
            else{
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/transport/RechercheTrajet.fxml"));
            root = loader.load();}
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
        menu.getScene().setRoot(root);
    }
    @FXML
    void redirectToOffres() {
        Parent root = null;

        try {
            if (userSession.hasRole(Role.Administrateur) ) {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/Recrutement/Offres.fxml"));
                root = loader.load();
            }
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
        menu.getScene().setRoot(root);
    }
    @FXML
    void redirectToDemandes() {
        Parent root = null;
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Recrutement/ListeDemande.fxml"));
            root = loader.load();
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
        menu.getScene().setRoot(root);
    }
    @FXML
    void redirectToAnalyseCv() {
        Parent root = null;
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Recrutement/AnalyseCVs.fxml"));
            root = loader.load();
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
        menu.getScene().setRoot(root);
    }
}
