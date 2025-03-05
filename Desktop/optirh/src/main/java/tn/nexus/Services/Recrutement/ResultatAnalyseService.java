package tn.nexus.Services.Recrutement;

import tn.nexus.Entities.Recrutement.ResultatAnalyse;
import tn.nexus.Utils.DBConnection;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class ResultatAnalyseService {

    private Connection cnx = DBConnection.getInstance().getConnection();
    private Statement st;
    private PreparedStatement ps;


    public void saveResultat(ResultatAnalyse resultat) {
        String query = "INSERT INTO resultat_analyse (nom, experience, technologies, linkedin) VALUES (?, ?, ?, ?)";
        try (PreparedStatement statement = cnx.prepareStatement(query)) {
            statement.setString(1, resultat.getNom());
            statement.setString(2, resultat.getExperience());
            statement.setString(3, resultat.getTechnologies());
            statement.setString(4, resultat.getLinkedin());
            statement.executeUpdate();
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    public List<ResultatAnalyse> getAllResultats() {
        List<ResultatAnalyse> resultats = new ArrayList<>();
        String query = "SELECT * FROM resultat_analyse";
        try (Statement statement = cnx.createStatement();
             ResultSet resultSet = statement.executeQuery(query)) {
            while (resultSet.next()) {
                ResultatAnalyse resultat = new ResultatAnalyse(
                        resultSet.getString("nom"),
                        resultSet.getString("experience"),
                        resultSet.getString("technologies"),
                        resultSet.getString("linkedin"),
                        resultSet.getString("matching")

                );
                resultats.add(resultat);
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return resultats;
    }
}
