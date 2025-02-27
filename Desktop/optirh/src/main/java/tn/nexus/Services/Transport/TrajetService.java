package tn.nexus.Services.Transport;

import tn.nexus.Entities.transport.Trajet;
import tn.nexus.Utils.DBConnection;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class TrajetService implements tn.nexus.Services.CRUD<Trajet> {
    private Connection con = DBConnection.getInstance().getConnection();

    @Override
    public int insert(Trajet trajet) throws SQLException {
        String req = "INSERT INTO trajet (type, station, depart, arrive) VALUES (?, ?, ?, ?)";
        try (PreparedStatement ps = con.prepareStatement(req, Statement.RETURN_GENERATED_KEYS)) {
            ps.setString(1, trajet.getType());
            ps.setString(2, trajet.getStation());
            ps.setString(3, trajet.getDepart());
            ps.setString(4, trajet.getArrive());

            int result = ps.executeUpdate();

            // Récupérer l'ID généré
            try (ResultSet rs = ps.getGeneratedKeys()) {
                if (rs.next()) {
                    trajet.setId(rs.getInt(1));
                }
            }

            return result;
        }
    }

    @Override
    public int update(Trajet trajet) throws SQLException {
        String req = "UPDATE trajet SET type = ?, station = ?, depart = ?, arrive = ? WHERE id = ?";
        try (PreparedStatement ps = con.prepareStatement(req)) {
            ps.setString(1, trajet.getType());
            ps.setString(2, trajet.getStation());
            ps.setString(3, trajet.getDepart());
            ps.setString(4, trajet.getArrive());
            ps.setInt(5, trajet.getId());

            return ps.executeUpdate();
        }
    }

    @Override
    public int delete(Trajet trajet) throws SQLException {
        String req = "DELETE FROM trajet WHERE id = ?";
        try (PreparedStatement ps = con.prepareStatement(req)) {
            ps.setInt(1, trajet.getId());
            return ps.executeUpdate();
        }
    }

    @Override
    public List<Trajet> showAll() throws SQLException {
        List<Trajet> trajets = new ArrayList<>();
        String req = "SELECT * FROM trajet";

        try (Statement st = con.createStatement();
             ResultSet rs = st.executeQuery(req)) {
            while (rs.next()) {
                Trajet trajet = new Trajet(
                        rs.getInt("id"),
                        rs.getString("type"),
                        rs.getString("station"),
                        rs.getString("depart"),
                        rs.getString("arrive")
                );
                trajets.add(trajet);
            }
        }
        return trajets;
    }

    public List<Trajet> getTrajetsByDepartAndArrive(String depart, String arrive) throws SQLException {
        List<Trajet> trajets = new ArrayList<>();
        String query = "SELECT * FROM trajet WHERE depart = ? AND arrive = ?";
        try (PreparedStatement ps = con.prepareStatement(query)) {
            ps.setString(1, depart);
            ps.setString(2, arrive);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    trajets.add(new Trajet(
                            rs.getInt("id"),
                            rs.getString("type"),
                            rs.getString("station"),
                            rs.getString("depart"),
                            rs.getString("arrive")
                    ));
                }
            } catch (SQLException e) {
                throw new RuntimeException(e);
            }
        }
        return trajets;
    }
}