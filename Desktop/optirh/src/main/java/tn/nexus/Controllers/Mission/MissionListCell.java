package tn.nexus.Controllers.Mission;

import javafx.scene.control.ListCell;
import tn.nexus.Entities.Mission.Mission;

public class MissionListCell extends ListCell<Mission> {

    @Override
    protected void updateItem(Mission mission, boolean empty) {
        super.updateItem(mission, empty);

        if (empty || mission == null) {
            setText(null);
            setGraphic(null);
            setStyle("");
        } else {
            setText(mission.getTitre());

            switch (mission.getStatus()) {
                case "To Do":
                    setStyle("-fx-background-color: #FFCCCB; -fx-border-color: #FF6666; -fx-border-radius: 5px; -fx-padding: 5px;");
                    break;
                case "In Progress":
                    setStyle("-fx-background-color: #FFD699; -fx-border-color: #FFA500; -fx-border-radius: 5px; -fx-padding: 5px;");
                    break;
                case "Done":
                    setStyle("-fx-background-color: #C1E1C1; -fx-border-color: #77DD77; -fx-border-radius: 5px; -fx-padding: 5px;");
                    break;
                default:
                    setStyle("-fx-background-color: #f0f0f0; -fx-border-color: #ccc; -fx-border-radius: 5px; -fx-padding: 5px;");
                    break;
            }
        }
    }
}