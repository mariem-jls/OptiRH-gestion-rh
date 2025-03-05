package tn.nexus.Services.Transport;

import tn.nexus.Entities.transport.Statistique;

import java.sql.Connection;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.List;

public class StatistiqueService {

    private Connection connection;

    public StatistiqueService(Connection connection) {
        this.connection = connection;
    }

    /**
     * Récupère le nombre de réservations par type de véhicule.
     */
    public List<Statistique> getReservationsParTypeVehicule() throws SQLException {
        List<Statistique> reservationsParType = new ArrayList<>();
        String query = "SELECT v.type, COUNT(r.id) AS nombre_reservations " +
                "FROM vehicule v " +
                "INNER JOIN reservationtrajet r ON v.id = r.vehicule_id " +
                "GROUP BY v.type " +
                "ORDER BY nombre_reservations DESC";

        try (Statement stmt = connection.createStatement();
             ResultSet rs = stmt.executeQuery(query)) {

            while (rs.next()) {
                String typeVehicule = rs.getString("type");
                int nombreReservations = rs.getInt("nombre_reservations");
                reservationsParType.add(new Statistique(0, typeVehicule, nombreReservations));
            }
        } catch (SQLException e) {
            System.err.println("Erreur lors de la récupération des réservations par type de véhicule : " + e.getMessage());
            throw e;
        }
        return reservationsParType;
    }

}