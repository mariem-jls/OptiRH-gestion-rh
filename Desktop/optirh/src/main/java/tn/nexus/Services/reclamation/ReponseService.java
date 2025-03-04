package tn.nexus.Services.reclamation;

import tn.nexus.Entities.reclamation.Reponse;
import tn.nexus.Services.CRUD;
import tn.nexus.Utils.DBConnection;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class ReponseService implements CRUD<Reponse> {
    private Connection con = DBConnection.getInstance().getConnection();

    @Override
    public int insert(Reponse reponse) throws SQLException {
        String req = "INSERT INTO reponse (description, date, reclamation_id, rating) VALUES (?, ?, ?, ?)";
        try (PreparedStatement ps = con.prepareStatement(req)) {
            ps.setString(1, reponse.getDescription());
            ps.setDate(2, new Date(reponse.getDate().getTime()));
            ps.setInt(3, reponse.getReclamationId());
            ps.setInt(4, reponse.getRating());
            return ps.executeUpdate();
        }
    }

    @Override
    public int update(Reponse reponse) throws SQLException {
        String req = "UPDATE reponse SET description = ?, date = ?, reclamation_id = ?, rating = ? WHERE id = ?";
        try (PreparedStatement ps = con.prepareStatement(req)) {
            ps.setString(1, reponse.getDescription());
            ps.setDate(2, new Date(reponse.getDate().getTime()));
            ps.setInt(3, reponse.getReclamationId());
            ps.setInt(4, reponse.getRating());
            ps.setInt(5, reponse.getId());
            return ps.executeUpdate();
        }
    }

    @Override
    public int delete(Reponse reponse) throws SQLException {
        String req = "DELETE FROM reponse WHERE id = ?";
        try (PreparedStatement ps = con.prepareStatement(req)) {
            ps.setInt(1, reponse.getId());
            return ps.executeUpdate();
        }
    }

    @Override
    public List<Reponse> showAll() throws SQLException {
        List<Reponse> reponses = new ArrayList<>();
        String req = "SELECT * FROM reponse";
        try (Statement st = con.createStatement(); ResultSet rs = st.executeQuery(req)) {
            while (rs.next()) {
                Reponse reponse = new Reponse(
                        rs.getInt("id"),
                        rs.getString("description"),
                        rs.getDate("date"),
                        rs.getInt("reclamation_id"),
                        rs.getInt("rating")
                );
                reponses.add(reponse);
            }
        }
        return reponses;
    }

    public List<Reponse> getReponsesByReclamationId(int reclamationId) throws SQLException {
        List<Reponse> reponses = new ArrayList<>();
        String req = "SELECT * FROM reponse WHERE reclamation_id = ?";
        try (PreparedStatement ps = con.prepareStatement(req)) {
            ps.setInt(1, reclamationId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    Reponse reponse = new Reponse(
                            rs.getInt("id"),
                            rs.getString("description"),
                            rs.getDate("date"),
                            rs.getInt("reclamation_id"),
                            rs.getInt("rating")
                    );
                    reponses.add(reponse);
                }
            }
        }
        return reponses;
    }

    public boolean updateRating(int reponseId, int rating) throws SQLException {
        String req = "UPDATE reponse SET rating = ? WHERE id = ?";
        try (PreparedStatement ps = con.prepareStatement(req)) {
            ps.setInt(1, rating);
            ps.setInt(2, reponseId);
            return ps.executeUpdate() > 0;
        }
    }
}
