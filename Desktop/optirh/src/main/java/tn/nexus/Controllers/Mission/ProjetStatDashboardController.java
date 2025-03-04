package tn.nexus.Controllers.Mission;

import javafx.fxml.FXML;
import javafx.scene.chart.ScatterChart;
import javafx.scene.chart.StackedBarChart;
import javafx.scene.chart.XYChart;
import javafx.scene.layout.VBox;
import tn.nexus.Entities.Mission.Projet;
import tn.nexus.Services.Mission.MissionService;
import tn.nexus.Services.Mission.ProjetService;
import tn.nexus.Entities.Mission.Mission;

import java.sql.SQLException;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;
import java.time.temporal.ChronoUnit;
import java.util.*;
import java.util.stream.Collectors;

public class ProjetStatDashboardController {

    @FXML
    private StackedBarChart<String, Number> projectProgressChart;
    @FXML private ScatterChart<Number, Number> deadlineChart;
    @FXML private VBox projectStatsBox;

    private final ProjetService projetService = new ProjetService();
    private final MissionService missionService = new MissionService();

    @FXML
    public void initialize() {
        loadProjectAnalytics();
    }

    private void loadProjectAnalytics() {
        try {
            List<Projet> projets = projetService.showAll2();

            // Progrès des projets
            XYChart.Series<String, Number> doneSeries = new XYChart.Series<>();
            doneSeries.setName("Missions Terminées");

            XYChart.Series<String, Number> pendingSeries = new XYChart.Series<>();
            pendingSeries.setName("Missions en Cours");

            for (Projet p : projets) {
                int done = missionService.getTasksByProjectAndStatus(p.getId(), "Done").size();
                int total = missionService.getTasksByProjectAndStatus(p.getId(), null).size();
                int pending = total - done;

                doneSeries.getData().add(new XYChart.Data<>(p.getNom(), done));
                pendingSeries.getData().add(new XYChart.Data<>(p.getNom(), pending));
            }

            projectProgressChart.getData().addAll(doneSeries, pendingSeries);

            // Diagramme des délais
            ScatterChart.Series<Number, Number> deadlineSeries = new ScatterChart.Series<>();
            deadlineSeries.setName("Délais des Projets");

            projets.forEach(p -> {
                long daysRemaining = ChronoUnit.DAYS.between(
                        LocalDate.now(),
                        p.getCreatedAt().toLocalDateTime().plusDays(30)
                );
                deadlineSeries.getData().add(new ScatterChart.Data<>(
                        p.getMissions().size(),
                        daysRemaining
                ));
            });

            deadlineChart.getData().add(deadlineSeries);

        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

}