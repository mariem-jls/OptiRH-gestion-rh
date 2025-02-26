package tn.nexus.Services.Evenement;

import tn.nexus.Entities.Evenement.Reservation_evenement;
import tn.nexus.Entities.User;
import tn.nexus.Services.CRUD;
import tn.nexus.Utils.DBConnection;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class Reservation_evenementServices implements CRUD<Reservation_evenement> {
    private final Connection con = DBConnection.getInstance().getConnection();

    @Override
    public int insert(Reservation_evenement reservationEvenement) throws SQLException {
        // Vérifie si l'utilisateur a déjà une réservation pour cet événement
        if (isReservationExists(1, reservationEvenement.getIdEvenement())) {
            System.out.println("⚠ L'utilisateur a déjà réservé cet événement !");
            return 0; // Bloque l'insertion
        }

        // Requête d'insertion
        String query = "INSERT INTO reservation_evenement (id_user, id_evenement, first_name, last_name, email, telephone, date_reservation) VALUES (?, ?, ?, ?, ?, ?, ?)";
        try (PreparedStatement ps = con.prepareStatement(query)) {
            ps.setInt(1, 3); // idUser fixe à 1
            ps.setInt(2, reservationEvenement.getIdEvenement());
            ps.setString(3, reservationEvenement.getFirstName());
            ps.setString(4, reservationEvenement.getLastName());
            ps.setString(5, reservationEvenement.getEmail());
            ps.setString(6, reservationEvenement.getTelephone());
            ps.setDate(7, Date.valueOf(reservationEvenement.getDateReservation()));

            return ps.executeUpdate();
        }
    }


    @Override
    public int update(Reservation_evenement reservationEvenement) throws SQLException {
        String query = "UPDATE reservation_evenement SET last_name = ?, first_name = ?, telephone = ?, email = ?, date_reservation = ? WHERE id_participation = ?";
        try (PreparedStatement ps = con.prepareStatement(query)) {
            ps.setString(1, reservationEvenement.getLastName());
            ps.setString(2, reservationEvenement.getFirstName());
            ps.setString(3, reservationEvenement.getTelephone());
            ps.setString(4, reservationEvenement.getEmail());
            ps.setDate(5, Date.valueOf(reservationEvenement.getDateReservation()));
            ps.setInt(6, reservationEvenement.getIdParticipation());
            return ps.executeUpdate();
        }
    }

    @Override
    public int delete(Reservation_evenement reservationEvenement) throws SQLException {
        String query = "DELETE FROM reservation_evenement WHERE id_participation = ?";
        try (PreparedStatement ps = con.prepareStatement(query)) {
            ps.setInt(1, reservationEvenement.getIdParticipation());
            return ps.executeUpdate();
        }
    }

    @Override
    public List<Reservation_evenement> showAll() throws SQLException {
        List<Reservation_evenement> reservations = new ArrayList<>();
        String query = "SELECT r.*, e.titre FROM reservation_evenement r JOIN evenement e ON r.id_evenement = e.id_evenement";

        try (PreparedStatement ps = con.prepareStatement(query); ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                reservations.add(new Reservation_evenement(
                        rs.getString("titre"),
                        rs.getString("first_name"),
                        rs.getString("last_name"),
                        rs.getString("email"),
                        rs.getString("telephone"),
                        rs.getDate("date_reservation").toLocalDate()
                ));
            }
        }
        return reservations;
    }

    public List<Reservation_evenement> getReservationsByUserID(User user) throws SQLException {
        List<Reservation_evenement> reservations = new ArrayList<>();
        String query = "SELECT r.*, e.titre, e.date_debut FROM reservation_evenement r JOIN evenement e ON r.id_evenement = e.id_evenement WHERE r.id_user = ?";

        try (PreparedStatement ps = con.prepareStatement(query)) {
            ps.setInt(1,3);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    Reservation_evenement reservation = new Reservation_evenement();
                    reservation.setIdParticipation(rs.getInt("id_participation"));
                    reservation.setIdUser(rs.getInt("id_user"));
                    reservation.setIdEvenement(rs.getInt("id_evenement"));
                    reservation.setTitreEvenement(rs.getString("titre"));
                    reservation.setLastName(rs.getString("last_name"));
                    reservation.setFirstName(rs.getString("first_name"));
                    reservation.setTelephone(rs.getString("telephone"));
                    reservation.setEmail(rs.getString("email"));

                    Date dateReservation = rs.getDate("date_reservation");
                    if (dateReservation != null) reservation.setDateReservation(dateReservation.toLocalDate());

                    Date dateDebut = rs.getDate("date_debut");
                    if (dateDebut != null) reservation.setDateDebut(dateDebut.toLocalDate());

                    reservations.add(reservation);
                }
            }
        }
        return reservations;
    }

    public List<Reservation_evenement> getReservationsByEvent(int eventId) throws SQLException {
        List<Reservation_evenement> reservations = new ArrayList<>();
        String query = "SELECT * FROM reservation_evenement WHERE id_evenement = ?";

        try (PreparedStatement ps = con.prepareStatement(query)) {
            ps.setInt(1, eventId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    reservations.add(new Reservation_evenement(
                            rs.getString("first_name"),
                            rs.getString("last_name"),
                            rs.getString("email"),
                            rs.getString("telephone"),
                            rs.getDate("date_reservation").toLocalDate()
                    ));
                }
            }
        }
        return reservations;
    }

    public boolean isReservationExists(int idUser, int idEvenement) throws SQLException {
        String query = "SELECT COUNT(*) FROM reservation_evenement WHERE id_user = ? AND id_evenement = ?";

        try (PreparedStatement ps = con.prepareStatement(query)) {
            ps.setInt(1, idUser);
            ps.setInt(2, idEvenement);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return rs.getInt(1) > 0;  // Si COUNT(*) > 0, réservation déjà existante
                }
            }
        }
        return false;
    }

}
