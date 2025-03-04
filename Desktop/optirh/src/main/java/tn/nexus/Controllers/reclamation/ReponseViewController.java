package tn.nexus.Controllers.reclamation;

import com.google.zxing.BarcodeFormat;
import com.google.zxing.WriterException;
import com.google.zxing.client.j2se.MatrixToImageWriter;
import com.google.zxing.common.BitMatrix;
import com.google.zxing.qrcode.QRCodeWriter;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.scene.image.Image;
import javafx.scene.image.ImageView;
import javafx.scene.control.TableColumn;
import javafx.scene.control.TableView;
import javafx.scene.control.cell.PropertyValueFactory;
import org.controlsfx.control.Rating;
import tn.nexus.Entities.reclamation.Reponse;
import tn.nexus.Services.reclamation.ReponseService;

import java.io.ByteArrayInputStream;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.sql.Date;
import java.sql.SQLException;
import java.util.List;

public class ReponseViewController {
    @FXML
    private TableView<Reponse> reponsesTable;

    @FXML
    private TableColumn<Reponse, String> descriptionColumn;

    @FXML
    private TableColumn<Reponse, Date> dateColumn;

    @FXML
    private TableColumn<Reponse, Integer> ratingColumn;

    @FXML
    private Rating ratingControl;

    @FXML
    private ImageView qrCodeImageView; // QR Code affiché ici

    private int reclamationId;
    private final ReponseService reponseService = new ReponseService();

    @FXML
    public void initialize() {
        // Liaison des colonnes du tableau avec les propriétés des objets
        descriptionColumn.setCellValueFactory(new PropertyValueFactory<>("description"));
        dateColumn.setCellValueFactory(new PropertyValueFactory<>("date"));
        ratingColumn.setCellValueFactory(new PropertyValueFactory<>("rating"));

        // Sélection d'une réponse pour afficher le QR code et le rating
        reponsesTable.getSelectionModel().selectedItemProperty().addListener((obs, oldSelection, newSelection) -> {
            if (newSelection != null) {
                // Mise à jour du rating
                ratingControl.setRating(newSelection.getRating());

                // Générer et afficher le QR code
                generateQRCode(newSelection.getDescription(), newSelection.getDate(), newSelection.getRating());
            }
        });

        // Mise à jour du rating quand l'utilisateur modifie la note
        ratingControl.ratingProperty().addListener((obs, oldRating, newRating) -> {
            Reponse selectedReponse = reponsesTable.getSelectionModel().getSelectedItem();
            if (selectedReponse != null) {
                try {
                    reponseService.updateRating(selectedReponse.getId(), (int) newRating.doubleValue());
                    loadReponses(); // Recharger les données après mise à jour
                } catch (SQLException e) {
                    e.printStackTrace();
                }
            }
        });
    }

    public void setReclamationId(int reclamationId) {
        this.reclamationId = reclamationId;
        loadReponses();
    }

    private void loadReponses() {
        try {
            List<Reponse> reponses = reponseService.getReponsesByReclamationId(reclamationId);
            ObservableList<Reponse> observableReponses = FXCollections.observableArrayList(reponses);
            reponsesTable.setItems(observableReponses);
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    /**
     * Génère un QR code contenant la description, la date et le rating de la réponse.
     */
    private void generateQRCode(String description, Date date, int rating) {
        QRCodeWriter qrCodeWriter = new QRCodeWriter();
        int width = 200;
        int height = 200;
        try {
            // Construire le texte du QR code
            String qrText = "Description: " + description +
                    "\nDate: " + date.toString() +
                    "\nRating: " + rating + " / 5";

            // Générer le QR code
            BitMatrix bitMatrix = qrCodeWriter.encode(qrText, BarcodeFormat.QR_CODE, width, height);
            ByteArrayOutputStream outputStream = new ByteArrayOutputStream();
            MatrixToImageWriter.writeToStream(bitMatrix, "PNG", outputStream);

            // Convertir le QR code en image JavaFX
            ByteArrayInputStream inputStream = new ByteArrayInputStream(outputStream.toByteArray());
            Image qrImage = new Image(inputStream);

            // Affichage du QR code dans l'ImageView
            qrCodeImageView.setImage(qrImage);
        } catch (WriterException | IOException e) {
            e.printStackTrace();
        }
    }
}