package tn.nexus.Controllers.Transport;

import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.scene.chart.PieChart;
import tn.nexus.Entities.transport.Statistique;
import tn.nexus.Services.Transport.StatistiqueService;
import tn.nexus.Utils.DBConnection;

import java.sql.SQLException;
import java.util.Comparator;
import java.util.List;



public class StatistiqueController {

    @FXML
    private PieChart pieChart;

    private StatistiqueService statistiqueService;

    public void initialize() {
        // Initialiser le service
        statistiqueService = new StatistiqueService(DBConnection.getInstance().getConnection());

        try {
            // Récupérer les réservations par type de véhicule
            List<Statistique> reservationsParType = statistiqueService.getReservationsParTypeVehicule();

            // Trier les réservations par nombre de réservations (du plus élevé au plus bas)
            reservationsParType.sort(Comparator.comparingInt(Statistique::getNombreReservations).reversed());

            // Créer les données pour le PieChart
            ObservableList<PieChart.Data> pieChartData = FXCollections.observableArrayList();
            for (Statistique stat : reservationsParType) {
                if (stat.getNombreReservations() > 0) {
                    pieChartData.add(new PieChart.Data(stat.getNom(), stat.getNombreReservations()));
                }
            }

            // Ajouter les données au PieChart
            pieChart.setData(pieChartData);
            pieChart.setTitle("Réservations par type de véhicule");

            // Personnaliser le PieChart
            pieChart.setClockwise(true); // Sens horaire
            pieChart.setLabelsVisible(true); // Afficher les labels
            pieChart.setStartAngle(90); // Angle de départ

        } catch (SQLException e) {
            e.printStackTrace();
        }
    }
}