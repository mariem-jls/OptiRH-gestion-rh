package tn.nexus.Controllers.Transport;

import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.input.MouseEvent;
import javafx.scene.layout.AnchorPane;

import java.io.IOException;

public class DashboardController {
    @FXML
    private AnchorPane dashContent; // Zone où afficher la gestion de projet

    @FXML
    private void ouvrirGestionTransport() {
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/GestionTrajet.fxml"));
            Parent projetView = loader.load();
            dashContent.getChildren().clear(); // Effacer le contenu actuel
            dashContent.getChildren().add(projetView); // Afficher la nouvelle vue
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    public void homeOnClick(MouseEvent mouseEvent) {
    }

    public void tab1onclick(MouseEvent mouseEvent) {
    }

    public void tab2onclick(MouseEvent mouseEvent) {
    }

    

    public void ouvrirGestionProjet(MouseEvent mouseEvent) {
    }

    public void ouvrirEvent(MouseEvent mouseEvent) {
    }
}
