package tn.nexus.Controllers.Evenement;

import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.AnchorPane;
import javafx.scene.layout.HBox;
import javafx.stage.Modality;
import javafx.stage.Stage;
import tn.nexus.Entities.Evenement.Evenement;
import tn.nexus.Entities.Evenement.StatusEvenement;
import tn.nexus.Services.Evenement.EvenementServices;
import tn.nexus.Utils.WrapWithSideBar;

import java.io.IOException;
import java.sql.SQLException;
import java.util.List;

public class ListeEvenementController implements WrapWithSideBar {

    @FXML
    private AnchorPane sideBar;


    @FXML
    private TableColumn<Evenement, Double> longitudeColumn;
    @FXML
    private TableColumn<Evenement, Double> latitudeColumn;
    @FXML
    private TableColumn<Evenement, StatusEvenement> statusColumn;


    @FXML
    private TextField searchField;
    @FXML
    private Button Ajouter_Evenement;

    @FXML
    private TableColumn<Evenement, Void> actionColumn;
    @FXML
    private TableColumn<Evenement, String> descriptionColumn;
    @FXML
    private TableColumn<Evenement, String> endDateColumn;
    @FXML
    private TableView<Evenement> eventsTable;
    @FXML
    private TableColumn<Evenement, String> imageColumn;
    @FXML
    private TableColumn<Evenement, String> locationColumn;
    @FXML
    private TableColumn<Evenement, Double> priceColumn;
    @FXML
    private TableColumn<Evenement, String> startDateColumn;
    @FXML
    private TableColumn<Evenement, String> timeColumn;
    @FXML
    private TableColumn<Evenement, String> titleColumn;

    // Instance du service événement
    private final EvenementServices serviceEvenement = new EvenementServices();
    private ObservableList<Evenement> evenementObservableList;
    private Evenement evenement =new Evenement();

    public void initialize() {
        initializeSideBar(sideBar);
        titleColumn.setCellValueFactory(new PropertyValueFactory<>("titre"));
        locationColumn.setCellValueFactory(new PropertyValueFactory<>("lieu"));
        priceColumn.setCellValueFactory(new PropertyValueFactory<>("prix"));
        startDateColumn.setCellValueFactory(new PropertyValueFactory<>("dateDebut"));
        endDateColumn.setCellValueFactory(new PropertyValueFactory<>("dateFin"));
        timeColumn.setCellValueFactory(new PropertyValueFactory<>("heure"));
        imageColumn.setCellValueFactory(new PropertyValueFactory<>("image"));
        statusColumn.setCellValueFactory(new PropertyValueFactory<>("status"));
        // Appliquer la couleur en fonction du statut
        statusColumn.setCellFactory(column -> new TableCell<Evenement, StatusEvenement>() {
            @Override
            protected void updateItem(StatusEvenement status, boolean empty) {
                super.updateItem(status, empty);
                setText(null); // Réinitialisation du texte
                setStyle(""); // Réinitialisation du style

                if (!empty && status != null) {
                    setText(status.toString()); // Afficher le texte du statut

                    switch (status) {
                        case A_VENIR:
                            setStyle("-fx-background-color: #77DD77; -fx-text-fill: black;");
                            break;
                        case EN_COURS:
                            setStyle("-fx-background-color: #FFA500; -fx-text-fill: black;");
                            break;
                        case TERMINE:
                            setStyle("-fx-background-color: #FF6666; -fx-text-fill: white;");
                            break;
                    }
                }
            }
        });


        refreshTable();// Charger les événements dans la table


        // Ajouter les boutons d'action dans la table
        addButtonToTable();

        // Recherche dynamique
        searchField.textProperty().addListener((observable, oldValue, newValue) -> {
            filterEvenements(newValue);
        });

    }

    private void filterEvenements(String searchText) {
        if (searchText == null || searchText.trim().isEmpty()) {
            eventsTable.setItems(evenementObservableList);
            return;
        }

        String lowerCaseFilter = searchText.toLowerCase();
        ObservableList<Evenement> filteredList = evenementObservableList.filtered(evenement ->
                evenement.getTitre().toLowerCase().contains(lowerCaseFilter) ||
                        evenement.getLieu().toLowerCase().contains(lowerCaseFilter)
        );

        eventsTable.setItems(filteredList);
        addButtonToTable();

    }

    private HBox createActionButtons(Evenement evenement) {
        Button btnModifier = new Button("✏");
        Button btnSupprimer = new Button("\uD83D\uDDD1 ");
        Button btnVoirReservation = new Button("Voir réservation");

        btnModifier.getStyleClass().add("btn-modifier");
        btnSupprimer.getStyleClass().add("btn-supprimer");
        btnVoirReservation.getStyleClass().add("btn-voir");

        btnModifier.setOnAction(event -> modifierEvenement(evenement));
        btnSupprimer.setOnAction(event -> supprimerEvenement(evenement));
        btnVoirReservation.setOnAction(event -> voirReservation(evenement));

        return new HBox(10, btnModifier, btnSupprimer, btnVoirReservation);
    }

    private void addButtonToTable() {
        actionColumn.setCellFactory(param -> new TableCell<>() {
            @Override
            protected void updateItem(Void item, boolean empty) {
                super.updateItem(item, empty);
                if (empty) {
                    setGraphic(null);
                } else {
                    Evenement evenement = getTableView().getItems().get(getIndex());
                    setGraphic(createActionButtons(evenement));
                }
            }
        });
    }

    private void modifierEvenement(Evenement evenement) {
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Evenement/ModifierEvenement.fxml"));
            Parent root = loader.load();

            ModifierEvenementController controller = loader.getController();
            controller.initData(evenement);
            controller.setListeEvenementController(this);

            Stage stage = new Stage();
            stage.setTitle("Modifier Événement");
            stage.setScene(new Scene(root));
            stage.initModality(Modality.APPLICATION_MODAL);

            stage.showAndWait(); // Attendre la fermeture pour rafraîchir la table
            refreshTable();
        } catch (IOException e) {
            System.out.println("Erreur ouverture modification : " + e.getMessage());
        }
    }
    private void supprimerEvenement(Evenement evenement) {
        if (evenement != null) {
            try {
                serviceEvenement.delete(evenement);
                refreshTable();
            } catch (SQLException e) {
                System.out.println("Erreur lors de la suppression : " + e.getMessage());
            }
        }
    }

    private void voirReservation(Evenement evenement) {
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Evenement/ListeResrvationByEvent.fxml"));
            Parent root = loader.load();

            ListeResrvationByEventController controller = loader.getController();
            controller.loadReservations(evenement.getIdEvenement());

            Stage stage = new Stage();
            stage.setTitle("Liste des Réservations - " + evenement.getTitre());
            stage.setScene(new Scene(root));
            stage.initModality(Modality.APPLICATION_MODAL);

            stage.showAndWait(); // Attendre la fermeture pour rafraîchir la table
            refreshTable();
        } catch (IOException e) {
            System.out.println("Erreur affichage réservations : " + e.getMessage());
        }
    }

    @FXML
    private void ouvrirFenetreAjout() {
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Evenement/AjouterEvenement.fxml"));
            Parent root = loader.load();

            AjouterEvenementController controller = loader.getController();
            controller.setListeEvenementController(this);

            Stage stage = new Stage();
            stage.setTitle("Ajouter un Événement");
            stage.setScene(new Scene(root));
            stage.initModality(Modality.APPLICATION_MODAL);

            stage.showAndWait(); // Attendre la fermeture pour rafraîchir
            refreshTable();
        } catch (IOException e) {
            System.out.println("Erreur ouverture ajout : " + e.getMessage());
        }
    }

    public void refreshTable() {
        try {
            List<Evenement> evenementList = serviceEvenement.showAll();
            evenementObservableList = FXCollections.observableArrayList(evenementList);
            eventsTable.setItems(evenementObservableList);
        } catch (SQLException e) {
            System.out.println("Erreur lors du rafraîchissement : " + e.getMessage());
        }
    }

    /**********************ouvrir map*******************/
    public void viewinmap(ActionEvent actionEvent) {
        try {

            String fxmlPath = "/Evenement/map.fxml";
            FXMLLoader loader = new FXMLLoader(getClass().getResource(fxmlPath));
            Parent root = loader.load();
            Scene scene = new Scene(root, 800, 600);
            Stage stage = new Stage();
            stage.setScene(scene);
            stage.setTitle("Carte Dynamique Des Evenement");
            stage.show();
        } catch (IOException e) {
            System.err.println("Erreur lors du chargement du fichier FXML: " + e.getMessage());
            e.printStackTrace();
        }
    }
}
