package tn.nexus.Controllers.Evenement;

import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.scene.control.Alert;
import javafx.scene.control.DatePicker;
import javafx.scene.control.TextArea;
import javafx.scene.control.TextField;
import javafx.stage.FileChooser;
import javafx.stage.Stage;
import tn.nexus.Entities.Evenement.Evenement;
import tn.nexus.Services.Evenement.EvenementServices;

import java.io.File;
import java.sql.SQLException;
import java.time.LocalDate;
import java.time.LocalTime;
import java.time.format.DateTimeParseException;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.image.Image;
import javafx.scene.image.ImageView;
import javafx.stage.FileChooser;
import javafx.stage.Stage;

import java.io.File;
import java.sql.SQLException;
import java.time.LocalDate;
import java.time.LocalTime;
import java.time.format.DateTimeParseException;

public class AjouterEvenementController {

    @FXML private DatePicker dateDebutField, dateFinField;
    @FXML private TextArea descriptionField;
    @FXML private TextField heureField, imageField, lieuField, titreField, prixField, latitudeField, longitudeField;
    @FXML private Label weatherLabel;
    @FXML private ProgressIndicator progressIndicator;
    @FXML private ImageView weatherIcon;



    private final EvenementServices serviceEvenement = new EvenementServices();
    private ListeEvenementController listeEvenementController;

    public void setListeEvenementController(ListeEvenementController controller) {
        this.listeEvenementController = controller;
    }

    @FXML
    public void initialize() {
        latitudeField.textProperty().addListener((obs, oldVal, newVal) -> handleFetchWeather());
        longitudeField.textProperty().addListener((obs, oldVal, newVal) -> handleFetchWeather());
        dateDebutField.valueProperty().addListener((obs, oldVal, newVal) -> handleFetchWeather());
        dateFinField.valueProperty().addListener((obs, oldVal, newVal) -> handleFetchWeather());
    }

    @FXML
    private void clearFields() {
        titreField.clear(); lieuField.clear(); descriptionField.clear(); prixField.clear();
        dateDebutField.setValue(null); dateFinField.setValue(null);
        heureField.clear(); imageField.clear();
    }

    private void showAlert(Alert.AlertType type, String title, String message) {
        Alert alert = new Alert(type);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }

    @FXML
    void choisirImage(ActionEvent event) {
        FileChooser fileChooser = new FileChooser();
        fileChooser.getExtensionFilters().add(
                new FileChooser.ExtensionFilter("Images (*.png, *.jpg, *.jpeg)", "*.png", "*.jpg", "*.jpeg")
        );
        File selectedFile = fileChooser.showOpenDialog(null);
        if (selectedFile != null) {
            imageField.setText(selectedFile.getAbsolutePath());
        }
    }

    private boolean validateFields() {
        if (titreField.getText().isEmpty() || lieuField.getText().isEmpty() ||
                descriptionField.getText().isEmpty() || imageField.getText().isEmpty() ||
                prixField.getText().isEmpty() || heureField.getText().isEmpty() || longitudeField.getText().isEmpty()|| latitudeField.getText().isEmpty()||
                dateDebutField.getValue() == null || dateFinField.getValue() == null) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Tous les champs sont requis !");
            return false;
        }
        try {
            Double.parseDouble(prixField.getText());
            Double.parseDouble(latitudeField.getText());
            Double.parseDouble(longitudeField.getText());
        } catch (NumberFormatException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Le prix, la latitude et la longitude doivent être des nombres valides !");
            return false;
        }
        try {
            LocalTime.parse(heureField.getText());
        } catch (DateTimeParseException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "L'heure doit être au format HH:mm !");
            return false;
        }
        return true;
    }

    @FXML
    void ajouterEvenement(ActionEvent event) throws SQLException {
        if (!validateFields()) return;

        Evenement evenement = new Evenement(
                titreField.getText(), lieuField.getText(), descriptionField.getText(),
                Double.parseDouble(prixField.getText()), imageField.getText(),
                dateDebutField.getValue(), dateFinField.getValue(), LocalTime.parse(heureField.getText()),
                Double.parseDouble(latitudeField.getText()), Double.parseDouble(longitudeField.getText())
        );

        serviceEvenement.insert(evenement);
        if (listeEvenementController != null) {
            listeEvenementController.refreshTable();
        }
        clearFields();
        ((Stage) titreField.getScene().getWindow()).close();
    }



    @FXML
    private void handleFetchWeather() {
        String latStr = latitudeField.getText();
        String lonStr = longitudeField.getText();
        LocalDate dateDebut = dateDebutField.getValue();
        LocalDate dateFin = dateFinField.getValue();

        if (latStr.isEmpty() || lonStr.isEmpty() || dateDebut == null) {
            return;
        }

        try {
            double lat = Double.parseDouble(latStr);
            double lon = Double.parseDouble(lonStr);
            progressIndicator.setVisible(true);
            weatherLabel.setText("⏳ Chargement...");

            WeatherApiClient client = new WeatherApiClient();
            String weatherData = client.getWeatherData(lat, lon, dateDebut.toString());
            String weatherDataFin = (dateFin != null) ? client.getWeatherData(lat, lon, dateFin.toString()) : null;

            if (weatherData != null) {
                String parsedData = WeatherDataParser.parseWeatherData(weatherData, dateDebut.toString());
                weatherLabel.setText("Début: " + parsedData);
                String iconCode = extractIconCode(weatherData, dateDebut.toString());
                if (iconCode != null) {
                    String iconUrl = WeatherDataParser.getWeatherIcon(iconCode);
                    weatherIcon.setImage(new Image(iconUrl));
                }
            }

            if (weatherDataFin != null) {
                String parsedDataFin = WeatherDataParser.parseWeatherData(weatherDataFin, dateFin.toString());
                weatherLabel.setText(weatherLabel.getText() + "\nFin: " + parsedDataFin);
                String iconCodeFin = extractIconCode(weatherDataFin, dateFin.toString());
                if (iconCodeFin != null) {
                    String iconUrlFin = WeatherDataParser.getWeatherIcon(iconCodeFin);
                    weatherIcon.setImage(new Image(iconUrlFin));
                }
            }

        } catch (NumberFormatException e) {
            weatherLabel.setText("❌ Latitude et longitude doivent être des nombres.");
        } finally {
            progressIndicator.setVisible(false);
        }
    }


    private String extractIconCode(String weatherData, String date) {
        try {
            ObjectMapper mapper = new ObjectMapper();
            JsonNode root = mapper.readTree(weatherData);
            JsonNode list = root.path("list");

            for (JsonNode node : list) {
                String forecastDate = node.path("dt_txt").asText().split(" ")[0];
                if (forecastDate.equals(date)) {
                    return node.path("weather").get(0).path("icon").asText();
                }
            }
        } catch (Exception e) {
            e.printStackTrace();
        }
        return null;
    }
}
