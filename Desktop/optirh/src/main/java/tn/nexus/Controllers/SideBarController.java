package tn.nexus.Controllers;

import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.control.Button;
import javafx.scene.layout.AnchorPane;
import javafx.scene.layout.HBox;
import tn.nexus.Services.Auth.UserSession;
import tn.nexus.Utils.Enums.Role;

public class SideBarController {
    @FXML
    private AnchorPane menu;
    @FXML
    private Button logoutButton;
    @FXML
    private HBox manageUsersButton;
    @FXML
    private HBox manageProjectButton;
    @FXML
    private HBox manageComplaintsButton;
    @FXML
    private HBox manageEventsButton;
    @FXML
    private HBox manageTransportButton;
    @FXML
    private HBox manageIncidentsButton;
    @FXML
    private HBox manageOffersButton;
    @FXML
    private HBox manageRequestButton;
    @FXML
    private HBox analyseCvButton;
    private UserSession userSession = UserSession.getInstance();

    @FXML
    private void initialize() {
        manageUsersButton.setVisible(false);
        manageProjectButton.setVisible(false);
        manageComplaintsButton.setVisible(false);
        manageEventsButton.setVisible(false);
        manageTransportButton.setVisible(false);
        manageOffersButton.setVisible(false);
        manageRequestButton.setVisible(false);
        analyseCvButton.setVisible(false);

        manageUsersButton.setManaged(false);
        manageProjectButton.setManaged(false);
        manageComplaintsButton.setManaged(false);
        manageEventsButton.setManaged(false);
        manageTransportButton.setManaged(false);
        manageOffersButton.setManaged(false);
        manageRequestButton.setManaged(false);
        analyseCvButton.setManaged(false);
        updateVisibilityBasedOnUserRole();
    }

    private void updateVisibilityBasedOnUserRole() {
        if (userSession.hasRole(Role.Administrateur)) {
            manageUsersButton.setVisible(true);
            manageProjectButton.setVisible(true);
            manageComplaintsButton.setVisible(true);
            manageEventsButton.setVisible(true);
            manageTransportButton.setVisible(true);
            manageOffersButton.setVisible(true);
            manageRequestButton.setVisible(true);
            analyseCvButton.setVisible(true);

            manageUsersButton.setManaged(true);
            manageProjectButton.setManaged(true);
            manageComplaintsButton.setManaged(true);
            manageEventsButton.setManaged(true);
            manageTransportButton.setManaged(true);
            manageOffersButton.setManaged(true);
            manageRequestButton.setManaged(true);
            analyseCvButton.setManaged(true);
        } else if (userSession.hasRole(Role.Chef_Projet)) {
            manageProjectButton.setVisible(true);
            manageComplaintsButton.setVisible(true);
            manageEventsButton.setVisible(true);
            manageTransportButton.setVisible(true);

            manageProjectButton.setManaged(true);
            manageComplaintsButton.setManaged(true);
            manageEventsButton.setManaged(true);
            manageTransportButton.setManaged(true);
        } else if (userSession.hasRole(Role.DQHS)) {
            manageTransportButton.setVisible(true);

            manageTransportButton.setManaged(true);
        } else if (userSession.hasRole(Role.Employe)) {
            manageProjectButton.setVisible(true);
            manageComplaintsButton.setVisible(true);
            manageEventsButton.setVisible(true);
            manageTransportButton.setVisible(true);
            
            manageProjectButton.setManaged(true);
            manageComplaintsButton.setManaged(true);
            manageEventsButton.setManaged(true);
            manageTransportButton.setManaged(true);
        } else if (userSession.hasRole(Role.Gestionnaire_Parc_auto)) {
            manageTransportButton.setVisible(true);

            manageTransportButton.setManaged(true);
        }
    }

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

            if (userSession.hasRole(Role.Administrateur) || userSession.hasRole(Role.Chef_Projet)) {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/Mission/Projet.fxml"));
                root = loader.load();
            }

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
            if (userSession.hasRole(Role.Administrateur) || userSession.hasRole(Role.Chef_Projet)) {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/reclamation/listeReclamation.fxml"));
                root = loader.load();
            } else {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/reclamation/listeReclamationfront.fxml"));
                root = loader.load();
            }
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
        menu.getScene().setRoot(root);
    }

    @FXML
    void redirectToEvenement() {
        Parent root = null;
        try {
            if (userSession.hasRole(Role.Administrateur) || userSession.hasRole(Role.Chef_Projet)) {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/Evenement/listeEvenemnt.fxml"));
                root = loader.load();
            } else {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/Evenement/EventFront.fxml"));
                root = loader.load();
            }
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
        menu.getScene().setRoot(root);
    }

    @FXML
    void redirectToTransport() {
        Parent root = null;
        try {
            if (userSession.hasRole(Role.Administrateur)) {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/transport/GestionTrajet.fxml"));
                root = loader.load();
            } else {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/transport/RechercheTrajet.fxml"));
                root = loader.load();
            }
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
        menu.getScene().setRoot(root);
    }

    @FXML
    void redirectToOffres() {
        Parent root = null;

        try {
            if (userSession.hasRole(Role.Administrateur)) {
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
