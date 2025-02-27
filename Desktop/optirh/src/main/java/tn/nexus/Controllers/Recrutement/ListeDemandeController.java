package tn.nexus.Controllers.Recrutement;

import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.Button;
import javafx.scene.control.TableCell;
import javafx.scene.control.TableColumn;
import javafx.scene.control.TableView;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.AnchorPane;
import javafx.scene.layout.HBox;
import javafx.stage.Stage;
import tn.nexus.Entities.Recrutement.Demande;
import tn.nexus.Services.Recrutement.DemandeService;
import tn.nexus.Utils.WrapWithSideBar;

import java.awt.*;
import java.io.File;
import java.io.IOException;
import java.sql.SQLException;
import java.util.List;

public class ListeDemandeController implements WrapWithSideBar {

    @FXML private TableView<Demande> tableDemandes;
    @FXML private TableColumn<Demande, Integer> colId;
    @FXML private TableColumn<Demande, Demande.Statut> colStatut;
    @FXML private TableColumn<Demande, String> colDescription;
    @FXML private TableColumn<Demande, String> colDate;
    @FXML private TableColumn<Demande, String> colNomComplet;
    @FXML private TableColumn<Demande, String> colEmail;
    @FXML private TableColumn<Demande, String> colTelephone;
    @FXML private TableColumn<Demande, String> colAdresse;
    @FXML private TableColumn<Demande, String> colDateDebutDisponible;
    @FXML private TableColumn<Demande, String> colSituationActuelle;
    @FXML private TableColumn<Demande, Void> colActions; // Pour les boutons

    @FXML private TableColumn<Demande, String> colFichier;

    private DemandeService demandeService = new DemandeService();

    @FXML
    private AnchorPane sideBar;

    @FXML
    public void initialize() {
        setupTableColumns();
        chargerDemandes();
        initializeSideBar(sideBar);
    }

    private void setupTableColumns() {
        colId.setCellValueFactory(new PropertyValueFactory<>("id"));
        colStatut.setCellValueFactory(new PropertyValueFactory<>("statut"));
        colDate.setCellValueFactory(new PropertyValueFactory<>("date"));
        colDescription.setCellValueFactory(new PropertyValueFactory<>("description"));
        colNomComplet.setCellValueFactory(new PropertyValueFactory<>("nomComplet"));
        colEmail.setCellValueFactory(new PropertyValueFactory<>("email"));
        colTelephone.setCellValueFactory(new PropertyValueFactory<>("telephone"));
        colAdresse.setCellValueFactory(new PropertyValueFactory<>("adresse"));
        colDateDebutDisponible.setCellValueFactory(new PropertyValueFactory<>("dateDebutDisponible"));
        colSituationActuelle.setCellValueFactory(new PropertyValueFactory<>("situationActuelle"));

        colActions.setCellFactory(param -> new TableCell<>() {
            private final Button btnModifier = new Button("Voir");
            private final Button btnSupprimer = new Button("Supprimer");
            private final HBox container = new HBox(5, btnModifier, btnSupprimer);

            {
                btnModifier.setOnAction(event -> handleModifier(getTableView().getItems().get(getIndex())));
                btnSupprimer.setOnAction(event -> handleSupprimer(getTableView().getItems().get(getIndex())));
            }

            @Override
            protected void updateItem(Void item, boolean empty) {
                super.updateItem(item, empty);
                if (empty) {
                    setGraphic(null);
                } else {
                    setGraphic(container);
                }
            }
        });

        colFichier.setCellValueFactory(new PropertyValueFactory<>("fichierPieceJointe"));

        colFichier.setCellFactory(column -> new TableCell<>() {
            final Button downloadButton = new Button("Télécharger");

            @Override
            protected void updateItem(String filePath, boolean empty) {
                super.updateItem(filePath, empty);
                if (empty || filePath == null || filePath.isEmpty()) {
                    setGraphic(null);
                } else {
                    downloadButton.setOnAction(e -> openFile(filePath));
                    setGraphic(downloadButton);
                }
            }
        });
    }

    private void chargerDemandes() {
        try {
            List<Demande> demandes = demandeService.showAll();
            tableDemandes.getItems().setAll(demandes);
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }


    private void handleModifier(Demande demande) {
        try {
            System.out.println("Modifier la demande : " + demande);

            // Charger l'interface ModifierDemande.fxml
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Recrutement/ModifierDemande.fxml"));
            Parent root = loader.load();

            // Obtenir le contrôleur et lui passer la demande sélectionnée
            ModifierDemandeController controller = loader.getController();
            controller.setDemande(demande); // Méthode à créer dans le contrôleur

            // Afficher la fenêtre
            Stage stage = new Stage();
            stage.setScene(new Scene(root));
            stage.setTitle("Modifier Demande");
            stage.showAndWait();

            // Rafraîchir la table après modification
            chargerDemandes();
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    private void handleSupprimer(Demande demande) {
        try {
            demandeService.delete(demande);
            tableDemandes.getItems().remove(demande);
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    private void openFile(String filePath) {
        File file = new File(filePath);
        if (file.exists()) {
            try {
                Desktop.getDesktop().open(file);
            } catch (IOException e) {
                e.printStackTrace();
            }
        } else {
            System.out.println("Fichier introuvable !");
        }
    }
}
