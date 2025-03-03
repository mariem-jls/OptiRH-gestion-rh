package tn.nexus.Controllers.Evenement;

import com.google.gson.Gson;
import com.google.gson.GsonBuilder;
import javafx.concurrent.Worker;
import javafx.fxml.FXML;
import javafx.scene.web.WebView;
import tn.nexus.Entities.Evenement.Evenement;
import tn.nexus.Services.Evenement.EvenementServices;

import java.sql.SQLException;
import java.time.LocalDate;
import java.time.LocalTime;
import java.util.List;

public class Map {
    @FXML
    private WebView webView;

    @FXML
    public void initialize() {
        // Récupérez la liste des événements
        EvenementServices evenementService = new EvenementServices();
        List<Evenement> evenements = null;
        try {
            evenements = evenementService.showAll();
        } catch (SQLException e) {
            throw new RuntimeException(e);
        }

        // Créez une instance de Gson avec les adaptateurs personnalisés
        Gson gson = new GsonBuilder()
                .registerTypeAdapter(LocalDate.class, new LocalDateAdapter())
                .registerTypeAdapter(LocalTime.class, new LocalDateTimeAdapter())
                .create();

        // Convertissez la liste en JSON
        String evenementsJson = gson.toJson(evenements);
        // Chargez la carte Leaflet depuis le fichier HTML
        String htmlPath = getClass().getResource("/Evenement/map.html").toExternalForm();
        webView.getEngine().load(htmlPath);

        // Injectez les données JSON dans la WebView
        webView.getEngine().getLoadWorker().stateProperty().addListener((obs, oldState, newState) -> {
            if (newState == Worker.State.SUCCEEDED) {
                String script = "addMarkers(" + evenementsJson + ");";
                webView.getEngine().executeScript(script);
            }
        });
    }

}
