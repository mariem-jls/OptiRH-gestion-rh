package tn.nexus.Controllers.Mission;

import javafx.application.Platform;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.scene.chart.*;
import javafx.scene.control.Alert;
import javafx.scene.control.TextArea;
import javafx.scene.layout.VBox;
import tn.nexus.Services.Mission.MissionService;
import tn.nexus.Services.Mission.ProjetService;
import tn.nexus.Entities.Mission.Mission;
import tn.nexus.Entities.Mission.Projet;
import tn.nexus.Utils.Mission.GeminiAnalysisUtil;

import java.sql.SQLException;
import java.util.*;
import java.util.stream.Collectors;

public class MissionStatistiqueController {

    @FXML private BarChart<String, Number> statusDistributionChart;
    @FXML private PieChart priorityPieChart;
    @FXML private LineChart<String, Number> timelineChart;
    @FXML private VBox statsContainer;

    private final MissionService missionService = new MissionService();
    private final ProjetService projetService = new ProjetService();

    @FXML
    public void initialize() {
        loadMissionStatistics();
        loadProjectStatistics();
    }

    private void loadMissionStatistics() {
        try {
            List<Mission> allMissions = missionService.showAll();

            if (allMissions.isEmpty()) {
                showInfoAlert("Statistiques", "Aucune mission disponible pour générer des statistiques.");
                return;
            }

            // Répartition des statuts des missions (Bar Chart)
            Map<String, Long> statusCount = allMissions.stream()
                    .collect(Collectors.groupingBy(Mission::getStatus, Collectors.counting()));

            XYChart.Series<String, Number> series = new XYChart.Series<>();
            series.setName("Nombre de missions par statut"); // Ajout du titre

            statusCount.forEach((status, count) ->
                    series.getData().add(new XYChart.Data<>(status, count)));

            statusDistributionChart.getData().clear();
            statusDistributionChart.getData().add(series);
            statusDistributionChart.setTitle("Répartition des missions par statut");

            // Calcul des missions en retard (Pie Chart)
            long overdue = allMissions.stream()
                    .filter(m -> m.getDateTerminer().before(new java.util.Date())
                            && !"Done".equals(m.getStatus()))
                    .count();

            long onTime = allMissions.size() - overdue;

            priorityPieChart.getData().clear();
            PieChart.Data overdueData = new PieChart.Data("En retard", overdue);
            PieChart.Data onTimeData = new PieChart.Data("À temps", onTime);

            priorityPieChart.getData().addAll(overdueData, onTimeData);
            priorityPieChart.setTitle("Missions en retard vs À temps");

            // Ajouter des labels avec pourcentages
            Platform.runLater(() -> {
                for (PieChart.Data data : priorityPieChart.getData()) {
                    double total = overdue + onTime;
                    double percentage = (data.getPieValue() / total) * 100;
                    data.setName(data.getName() + " (" + String.format("%.1f", percentage) + "%)");
                }
            });

        } catch (SQLException e) {
            showErrorAlert("Erreur", "Erreur lors du chargement des missions: " + e.getMessage());
        }
    }

    private void loadProjectStatistics() {
        try {
            List<Projet> projects = projetService.showAll2();

            if (projects.isEmpty()) {
                showInfoAlert("Statistiques", "Aucun projet disponible pour générer des statistiques.");
                return;
            }

            XYChart.Series<String, Number> seriesProj = new XYChart.Series<>();
            seriesProj.setName("Nombre de missions terminées par projet");

            for (Projet p : projects) {
                int missionCount = missionService.getTasksByProjectAndStatus(p.getId(), "Done").size();
                seriesProj.getData().add(new XYChart.Data<>(p.getNom(), missionCount));
            }

            timelineChart.getData().clear();
            timelineChart.getData().add(seriesProj);
            timelineChart.setTitle("Progression des projets");

        } catch (SQLException e) {
            showErrorAlert("Erreur", "Erreur lors du chargement des projets: " + e.getMessage());
        }
    }

    @FXML
    private void generateSmartInsights() {
        showInfoAlert("Analyse en cours", "Veuillez patienter pendant la génération des insights...");

        new Thread(() -> {
            try {
                String dataSummary = buildDataSummary();
                String prompt = "Analyse les données suivantes et génère des insights détaillés :\n" + dataSummary;
                final String insights = GeminiAnalysisUtil.generateText(prompt);

                Platform.runLater(() -> {
                    TextArea insightArea = new TextArea(insights);
                    insightArea.setEditable(false);
                    insightArea.setWrapText(true);
                    insightArea.setPrefHeight(400);
                    statsContainer.getChildren().add(insightArea);
                });

            } catch (Exception e) {
                Platform.runLater(() -> showErrorAlert("Erreur d'analyse", "Impossible de générer l'analyse: " + e.getMessage()));
            }
        }).start();
    }

    private String buildDataSummary() {
        StringBuilder summary = new StringBuilder();
        try {
            List<Projet> projets = projetService.showAll2();
            List<Mission> missions = missionService.showAll();

            summary.append("=== Statistiques Globales ===\n")
                    .append("- Projets Totaux: ").append(projets.size()).append("\n")
                    .append("- Missions Totales: ").append(missions.size()).append("\n\n");

            Map<String, Long> statusCount = missions.stream()
                    .collect(Collectors.groupingBy(Mission::getStatus, Collectors.counting()));

            summary.append("=== Répartition des Statuts ===\n");
            statusCount.forEach((status, count) ->
                    summary.append("- ").append(status).append(": ").append(count).append("\n"));

        } catch (SQLException e) {
            summary.append("Erreur lors de la récupération des données : ").append(e.getMessage());
        }
        return summary.toString();
    }

    private void showInfoAlert(String title, String message) {
        Platform.runLater(() -> {
            Alert alert = new Alert(Alert.AlertType.INFORMATION);
            alert.setTitle(title);
            alert.setHeaderText(null);
            alert.setContentText(message);
            alert.show();
        });
    }

    private void showErrorAlert(String title, String message) {
        Platform.runLater(() -> {
            Alert alert = new Alert(Alert.AlertType.ERROR);
            alert.setTitle(title);
            alert.setHeaderText(null);
            alert.setContentText(message);
            alert.showAndWait();
        });
    }
}
