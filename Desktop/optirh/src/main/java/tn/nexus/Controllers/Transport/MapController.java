package tn.nexus.Controllers.Transport;

import javafx.concurrent.Worker;
import javafx.fxml.FXML;
import javafx.scene.web.WebView;
import netscape.javascript.JSObject;
import tn.nexus.Entities.transport.Trajet;
import tn.nexus.Services.Transport.TrajetService;

import java.sql.SQLException;
import java.util.List;

public class MapController {

    @FXML
    private WebView webView;

    @FXML
    public void initialize() {
        // Activer JavaScript
        webView.getEngine().setJavaScriptEnabled(true);

        // Charger le fichier HTML
        String mapHtmlPath = getClass().getResource("/transport/map.html").toExternalForm();
        webView.getEngine().load(mapHtmlPath);

        // Attendre que la page soit chargée
        webView.getEngine().getLoadWorker().stateProperty().addListener((observable, oldValue, newValue) -> {
            if (newValue == Worker.State.SUCCEEDED) {
                // Récupérer les trajets depuis la base de données
                TrajetService trajetService = new TrajetService();
                try {
                    List<Trajet> trajets = trajetService.showAll();

                    // Convertir les trajets en format JSON (uniquement les coordonnées)
                    String trajetsJson = convertTrajetsToJson(trajets);

                    // Passer les données à JavaScript
                    JSObject window = (JSObject) webView.getEngine().executeScript("window");
                    window.call("drawRoutes", trajetsJson);
                } catch (SQLException e) {
                    e.printStackTrace();
                }
            }
        });
    }

    private String convertTrajetsToJson(List<Trajet> trajets) {
        StringBuilder json = new StringBuilder("[");
        for (Trajet trajet : trajets) {
            json.append("{")
                    .append("\"start\": [").append(trajet.getLatitudeDepart()).append(",").append(trajet.getLongitudeDepart()).append("],")
                    .append("\"end\": [").append(trajet.getLatitudeArrivee()).append(",").append(trajet.getLongitudeArrivee()).append("]")
                    .append("},");
        }
        if (trajets.size() > 0) {
            json.deleteCharAt(json.length() - 1); // Supprimer la dernière virgule
        }
        json.append("]");
        return json.toString();
    }
}