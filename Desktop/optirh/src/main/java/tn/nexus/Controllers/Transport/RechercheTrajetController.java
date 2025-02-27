package tn.nexus.Controllers.Transport;

import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.image.Image;
import javafx.scene.image.ImageView;
import javafx.scene.paint.Color;
import javafx.scene.text.Font;
import tn.nexus.Entities.transport.Trajet;
import tn.nexus.Entities.transport.Vehicule;
import tn.nexus.Services.Transport.TrajetService;
import tn.nexus.Services.Transport.VehiculeService;


import java.net.URL;
import java.sql.SQLException;
import java.util.List;
import java.util.ResourceBundle;

public class RechercheTrajetController {

    @FXML private TextField departField;
    @FXML private TextField arriveField;
    @FXML private TableView<Vehicule> vehiculeTable;
    @FXML private TableColumn<Vehicule, String> typeColumn;
    @FXML private TableColumn<Vehicule, String> disponibiliteColumn;
    @FXML private TableColumn<Vehicule, Integer> placesColumn;
    @FXML private TableColumn<Vehicule, Void> actionColumn;
    @FXML private Label errorMessage;

    private final TrajetService trajetService = new TrajetService();
    private final VehiculeService vehiculeService = new VehiculeService();

    @FXML
    public void initialize() {
        // Configurer les colonnes de la TableView avec PropertyValueFactory
        typeColumn.setCellValueFactory(new PropertyValueFactory<>("type"));
        disponibiliteColumn.setCellValueFactory(new PropertyValueFactory<>("disponibilite"));
        placesColumn.setCellValueFactory(new PropertyValueFactory<>("nbrplace"));

        // Ajouter un bouton "Réserver" dans la colonne Action
        actionColumn.setCellFactory(param -> new TableCell<>() {
            private final Button reserverButton = new Button("Réserver");

            {
                reserverButton.setOnAction(event -> {
                    Vehicule vehicule = getTableView().getItems().get(getIndex());
                    handleReserver(vehicule);
                });
            }

            @Override
            protected void updateItem(Void item, boolean empty) {
                super.updateItem(item, empty);
                if (empty) {
                    setGraphic(null);
                } else {
                    setGraphic(reserverButton);
                }
            }
        });

        // Ajouter des icônes pour la colonne "Type"
        typeColumn.setCellFactory(param -> new TableCell<>() {
            private final ImageView imageView = new ImageView();

            @Override
            protected void updateItem(String type, boolean empty) {
                super.updateItem(type, empty);
                if (empty || type == null) {
                    setGraphic(null);
                } else {
                    try {
                        Image image = new Image(getClass().getResourceAsStream("/icons/" + type.toLowerCase() + ".png"));
                        imageView.setImage(image);
                        setGraphic(imageView);
                    } catch (Exception e) {
                        // Si l'icône n'est pas trouvée, afficher le texte du type
                        setText(type);
                        setGraphic(null);
                    }
                }
            }
        });
    }

    @FXML
    public void handleRechercher() {
        String depart = departField.getText();
        String arrive = arriveField.getText();

        if (depart.isEmpty() || arrive.isEmpty()) {
            showError("Veuillez entrer un point de départ et d'arrivée.");
            return;
        }

        try {
            // Rechercher les trajets correspondants
            List<Trajet> trajets = trajetService.getTrajetsByDepartAndArrive(depart, arrive);
            if (trajets.isEmpty()) {
                showError("Aucun trajet trouvé pour ces points.");
                return;
            }

            // Récupérer les véhicules disponibles pour ces trajets
            vehiculeTable.getItems().clear();
            for (Trajet trajet : trajets) {
                List<Vehicule> vehicules = vehiculeService.getVehiculesByTrajetId(trajet.getId());
                for (Vehicule vehicule : vehicules) {
                    if (vehicule.getNbrplace() > 0) {
                        vehiculeTable.getItems().add(vehicule);
                    }
                }
            }

            if (vehiculeTable.getItems().isEmpty()) {
                showError("Aucun véhicule disponible pour ces trajets.");
            }
        } catch (SQLException e) {
            showError("Erreur lors de la recherche : " + e.getMessage());
        }
    }

    private void handleReserver(Vehicule vehicule) {
        try {
            if (vehicule.getNbrplace() <= 0) {
                showError("Ce véhicule n'a plus de places disponibles.");
                return;
            }

            // Réserver une place
            vehicule.setNbrplace(vehicule.getNbrplace() - 1);
            vehiculeService.update(vehicule);

            // Mettre à jour la TableView
            vehiculeTable.refresh();

            showSuccess("Réservation réussie !");
        } catch (SQLException e) {
            showError("Erreur lors de la réservation : " + e.getMessage());
        }
    }

    private void showError(String message) {
        errorMessage.setText(message);
        errorMessage.setTextFill(Color.RED);
        errorMessage.setVisible(true);
    }

    private void showSuccess(String message) {
        errorMessage.setText(message);
        errorMessage.setTextFill(Color.GREEN);
        errorMessage.setVisible(true);
    }
}