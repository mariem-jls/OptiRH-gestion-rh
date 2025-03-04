package tn.nexus.Controllers.Recrutement;

import javafx.application.Platform;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.concurrent.Task;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.AnchorPane;
import javafx.stage.Stage;
import org.apache.pdfbox.pdmodel.PDDocument;
import org.apache.pdfbox.text.PDFTextStripper;
import tn.nexus.Entities.Recrutement.Demande;
import tn.nexus.Entities.Recrutement.Offre;
import tn.nexus.Entities.Recrutement.ResultatAnalyse;
import tn.nexus.Services.Recrutement.DemandeService;
import tn.nexus.Services.Recrutement.OffreService;
import tn.nexus.Services.Recrutement.ResultatAnalyseService;
import tn.nexus.Utils.WrapWithSideBar;

import java.io.File;
import java.io.IOException;
import java.sql.SQLException;
import java.util.List;

import static tn.nexus.Utils.TextSimilarityUtil.calculateMatchPercentage;

public class AnalyseCVsController implements  WrapWithSideBar {

    @FXML
    private ComboBox<Offre> offresComboBox;

    @FXML
    private ListView<String> cvListView;

    @FXML
    private Button analyserButton;

    @FXML
    private TableView<ResultatAnalyse> resultatTable;

    @FXML
    private TableColumn<ResultatAnalyse, String> colNom;

    @FXML
    private TableColumn<ResultatAnalyse, String> colExperience;

    @FXML
    private TableColumn<ResultatAnalyse, String> colTechnologies;

    @FXML
    private TableColumn<ResultatAnalyse, String> colLinkedin;
    @FXML
    private TableColumn<ResultatAnalyse, String> colMatch;
    @FXML
    private AnchorPane sideBar;

    private DemandeService demandeService = new DemandeService();
    private OffreService offreService = new OffreService();

    private final ObservableList<ResultatAnalyse> resultats = FXCollections.observableArrayList();
    private final ResultatAnalyseService resultatService = new ResultatAnalyseService();
    @FXML
    private void initialize() {

        initializeSideBar(sideBar);
        chargerOffres();
        offresComboBox.setOnAction(event -> chargerCVs());
        analyserButton.setOnAction(event -> analyserCVs());

        // Configuration  des colonnes
        colNom.setCellValueFactory(new PropertyValueFactory<>("nom"));
        colExperience.setCellValueFactory(new PropertyValueFactory<>("experience"));
        colTechnologies.setCellValueFactory(new PropertyValueFactory<>("technologies")); // Contenu brut ici
        colLinkedin.setCellValueFactory(new PropertyValueFactory<>("linkedin"));
        colMatch.setCellValueFactory(new PropertyValueFactory<>("matchPercentage"));

        resultatTable.setItems(resultats);
        colNom.setCellFactory(col -> {
            TableCell<ResultatAnalyse, String> cell = new TableCell<>() {
                @Override
                protected void updateItem(String item, boolean empty) {
                    super.updateItem(item, empty);
                    setText(empty ? null : item);
                }
            };

            cell.setOnMouseClicked(event -> {
                if (!cell.isEmpty()) {
                    ResultatAnalyse selectedCV = cell.getTableView().getItems().get(cell.getIndex());
                    redirectToDemandeDetails(selectedCV);
                }
            });

            return cell;
        });
    }

    private void chargerOffres() {
        try {
            List<Offre> offres = offreService.showAll();
            offresComboBox.getItems().setAll(offres);
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    private void chargerCVs() {
        Offre selectedOffre = offresComboBox.getValue();
        if (selectedOffre != null) {
            try {
                List<String> cvFiles = demandeService.getCVsByOffre(selectedOffre.getId());
                cvListView.getItems().setAll(cvFiles);
            } catch (SQLException e) {
                e.printStackTrace();
            }
        }
    }
    @FXML
    private void analyserCVs() {
        resultats.clear();
        Offre selectedOffer = offresComboBox.getValue();

        if (selectedOffer == null) {
            showAlert("Aucune offre sélectionnée !");
            return;
        }

        String offreDescription = selectedOffer.getDescription();
        List<String> cvPaths = cvListView.getItems();

        Task<Void> task = new Task<Void>() {
            @Override
            protected Void call() throws Exception {
                for (String cvPath : cvPaths) {
                    try {
                        PDDocument document = PDDocument.load(new File(cvPath));
                        PDFTextStripper stripper = new PDFTextStripper();
                        String rawContent = stripper.getText(document);
                        document.close();

                        // Calcul du pourcentage de correspondance
                        double matchPercentage = calculateMatchPercentage(rawContent, offreDescription);
                        String formattedPercentage = String.format("%.2f%%", matchPercentage);

                        ResultatAnalyse analyse = new ResultatAnalyse(
                                new File(cvPath).getName(),
                                "N/A",
                                rawContent,
                                "N/A",
                                formattedPercentage
                        );

                        Platform.runLater(() -> {
                            resultats.add(analyse);
                            resultatService.saveResultat(analyse);
                        });
                    } catch (Exception e) {
                        Platform.runLater(() -> {
                            resultats.add(new ResultatAnalyse(
                                    new File(cvPath).getName(),
                                    "Erreur",
                                    "Échec analyse : " + e.getMessage(),
                                    "N/A",
                                    "0.00%"
                            ));
                        });
                    }
                }
                return null;
            }
        };

        new Thread(task).start();
    }
    // Méthode de redirection
    private void redirectToDemandeDetails(ResultatAnalyse cv) {
        try {
            // Extraire le nom du fichier sans le chemin
            String fileName = new File(cv.getNom()).getName();

            Demande demandeAssociee = demandeService.getDemandeByCV(fileName);
            if (demandeAssociee == null) {
                showAlert("Aucune demande associée à ce CV.");
                return;
            }

            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Recrutement/ModifierDemande.fxml"));
            Parent root = loader.load();

            ModifierDemandeController controller = loader.getController();
            controller.setDemande(demandeAssociee);

            Stage newStage = new Stage();
            newStage.setScene(new Scene(root));
            newStage.setTitle("Modifier la demande");
            newStage.show();

        } catch (IOException | SQLException e) {
            showAlert("Erreur : " + e.getMessage());
        }
    }

    private void showAlert(String message) {
        Alert alert = new Alert(Alert.AlertType.WARNING);
        alert.setTitle("Attention");
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }

    @Override
    public void initializeSideBar(AnchorPane sideBar) {
        WrapWithSideBar.super.initializeSideBar(sideBar);
    }
}
