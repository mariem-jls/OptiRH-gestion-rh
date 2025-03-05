package tn.nexus.Controllers.Transport;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.AnchorPane;
import javafx.scene.web.WebEngine;
import javafx.scene.web.WebView;
import tn.nexus.Entities.transport.ReservationTrajet;
import tn.nexus.Entities.transport.Trajet;
import tn.nexus.Entities.transport.Vehicule;
import tn.nexus.Services.Transport.PayPalPaymentService;
import tn.nexus.Services.Transport.ReservationTrajetService;
import tn.nexus.Services.Transport.TrajetService;
import tn.nexus.Services.Transport.VehiculeService;
import tn.nexus.Utils.WrapWithSideBar;

import java.sql.SQLException;
import java.util.List;

public class RechercheTrajetController  implements WrapWithSideBar {

    // Composants FXML
    @FXML private TextField departField;
    @FXML private TextField arriveField;
    @FXML private TableView<Vehicule> vehiculeTable;
    @FXML private TableColumn<Vehicule, String> typeColumn;
    @FXML private TableColumn<Vehicule, String> disponibiliteColumn;
    @FXML private TableColumn<Vehicule, Integer> placesColumn;
    @FXML private TableColumn<Vehicule, Void> actionColumn;
    @FXML private Label errorMessage;
    @FXML
    private AnchorPane sideBar;
    @FXML private WebView webView; // Référence au WebVie


    // Services
    private final TrajetService trajetService = new TrajetService();
    private final VehiculeService vehiculeService = new VehiculeService();
    private final ReservationTrajetService reservationTrajetService = new ReservationTrajetService();



    // Méthode d'initialisation
    @FXML
    public void initialize() {
        initializeSideBar(sideBar);


        String mapHtmlPath = getClass().getResource("/transport/map.html").toExternalForm();
        webView.getEngine().load(mapHtmlPath);


        // Configurer les colonnes de la TableView
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
                    Vehicule vehicule = getTableView().getItems().get(getIndex());
                    if (vehicule.getNbrplace() <= 0 || vehicule.getDisponibilite().equalsIgnoreCase("Indisponible")) {
                        reserverButton.setDisable(true); // Désactiver le bouton si le véhicule est indisponible
                    } else {
                        reserverButton.setDisable(false); // Activer le bouton si le véhicule est disponible
                    }
                    setGraphic(reserverButton);
                }
            }
        });
    }
    // Gérer la recherche de trajets
    @FXML
    public void handleRechercher() {
        String depart = departField.getText();
        String arrive = arriveField.getText();

        hideError();

        // Validation des entrées
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
            // Vérifier si le véhicule a des places disponibles
            if (vehicule.getNbrplace() <= 0) {
                showError("Ce véhicule n'a plus de places disponibles.");
                return;
            }

            // Récupérer l'ID de l'utilisateur connecté
            int userId = 1; // Remplacez par l'ID de l'utilisateur connecté

            // Créer une nouvelle réservation
            ReservationTrajet reservation = new ReservationTrajet();
            reservation.setDisponibilite("Disponible"); // Statut de disponibilité
            reservation.setVehiculeId(vehicule.getId()); // ID du véhicule
            reservation.setTrajetId(vehicule.getTrajetId()); // ID du trajet
            reservation.setUserId(userId); // ID de l'utilisateur

            // Insérer la réservation dans la base de données
            int reservationId = reservationTrajetService.insert(reservation);
            if (reservationId > 0) {
                // Décrémenter le nombre de places disponibles
                vehicule.setNbrplace(vehicule.getNbrplace() - 1);

                // Incrémenter le nombre de réservations
                vehicule.setNbrReservation(vehicule.getNbrReservation() + 1);

                // Mettre à jour la disponibilité si le nombre de places atteint 0
                if (vehicule.getNbrplace() == 0) {
                    vehicule.setDisponibilite("Indisponible");
                }

                // Mettre à jour le véhicule dans la base de données
                vehiculeService.update(vehicule);

                // Mettre à jour la TableView
                vehiculeTable.refresh();

                // Afficher un message de succès
                showSuccess("Réservation réussie ! Redirection vers PayPal en cours...");

                // Rediriger l'utilisateur vers PayPal
                redirectToPayPal(vehicule);
            } else {
                showError("Échec de la réservation.");
            }
        } catch (SQLException e) {
            showError("Erreur lors de la réservation : " + e.getMessage());
        } catch (IllegalArgumentException e) {
            showError("Erreur : " + e.getMessage());
        }
    }
    // Afficher un message d'erreur
    private void showError(String message) {
        errorMessage.setText(message);
        errorMessage.setTextFill(javafx.scene.paint.Color.RED);
        errorMessage.setVisible(true);
    }

    // Afficher un message de succès
    private void showSuccess(String message) {
        errorMessage.setText(message);
        errorMessage.setTextFill(javafx.scene.paint.Color.GREEN);
        errorMessage.setVisible(true);
    }

    private void hideError() {
        errorMessage.setVisible(false); // Masquer le message d'erreur
    }

    private void redirectToPayPal(Vehicule vehicule) {
        // Montant du paiement (par exemple, 10.00 USD)
        double amount = 10.00; // Vous pouvez ajuster ce montant en fonction de votre logique
        String currency = "USD"; // Devise
        String description = "Paiement pour la réservation du véhicule " + vehicule.getType();

        // Créer un paiement PayPal et récupérer l'URL d'approbation
        PayPalPaymentService payPalService = new PayPalPaymentService();
        String approvalUrl = payPalService.createPayment(amount, currency, description);

        if (approvalUrl != null) {
            // Rediriger l'utilisateur vers l'URL d'approbation PayPal
            WebEngine webEngine = webView.getEngine();
            webEngine.load(approvalUrl);
        } else {
            showError("Erreur lors de la création du paiement PayPal.");
        }
    }

} 