package tn.nexus.Utils;

import javafx.fxml.FXMLLoader;
import javafx.scene.layout.AnchorPane;

public interface WrapWithSideBar {
    default public void initializeSideBar(AnchorPane sideBar) {
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/SideBar.fxml"));
            AnchorPane sideBarPane = loader.load();
            sideBar.getChildren().clear();
            sideBar.getChildren().add(sideBarPane);
        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}
