package tn.nexus.Services.Mission;

import tn.nexus.Entities.Mission.Projet;
import tn.nexus.Entities.User;
import tn.nexus.Services.CRUD;
import tn.nexus.Utils.DBConnection;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class ProjetService implements CRUD<Projet> {
    private final Connection cnx = DBConnection.getInstance().getConnection();

    // Méthodes CRUD
    @Override
    public int insert(Projet project) throws SQLException {
        String req = "INSERT INTO projects (nom, description, created_by, created_at) VALUES (?, ?, ?, ?)";
        try (PreparedStatement ps = cnx.prepareStatement(req)) {
            ps.setString(1, project.getNom());
            ps.setString(2, project.getDescription());
            ps.setInt(3, project.getCreatedBy());
            ps.setTimestamp(4, project.getCreatedAt());
            return ps.executeUpdate();
        }
    }

    @Override
    public int update(Projet project) throws SQLException {
        String req = "UPDATE projects SET nom = ?, description = ? WHERE id = ?";
        try (PreparedStatement ps = cnx.prepareStatement(req)) {
            ps.setString(1, project.getNom());
            ps.setString(2, project.getDescription());
            ps.setInt(3, project.getId());
            return ps.executeUpdate();
        }
    }

    @Override
    public int delete(Projet project) throws SQLException {
        String req = "DELETE FROM projects WHERE id = ?";
        try (PreparedStatement ps = cnx.prepareStatement(req)) {
            ps.setInt(1, project.getId());
            return ps.executeUpdate();
        }
    }
    public List<User> getAllChefProjet() throws SQLException {
        List<User> users = new ArrayList<>();
        String req = "SELECT id, nom FROM users WHERE role = 'Chef_Projet'"; // Filtre par rôle
        try (Statement st = cnx.createStatement();
             ResultSet rs = st.executeQuery(req)) {
            while (rs.next()) {
                users.add(new User(
                        rs.getInt("id"),
                        rs.getString("nom")
                ));
            }
        }
        return users;
    }
    @Override
    public List<Projet> showAll() throws SQLException {
        List<Projet> projects = new ArrayList<>();
        String req = "SELECT * FROM projects";
        try (Statement st = cnx.createStatement();
             ResultSet rs = st.executeQuery(req)) {
            while (rs.next()) {
                Projet project = new Projet(
                        rs.getInt("id"),
                        rs.getString("nom"),
                        rs.getString("description"),
                        rs.getTimestamp("created_at"),
                        rs.getInt("created_by")
                );
                projects.add(project);
            }
        }
        return projects;
    }

    // Méthodes supplémentaires
    public List<Projet> showAll2() throws SQLException {
        List<Projet> projets = new ArrayList<>();
        String req = "SELECT p.id, p.nom, p.description, p.created_at, u.nom AS user_nom " +
                "FROM Projects p " +
                "JOIN Users u ON p.created_by = u.id";
        try (PreparedStatement ps = cnx.prepareStatement(req);
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                Projet projet = new Projet();
                projet.setId(rs.getInt("id"));
                projet.setNom(rs.getString("nom"));
                projet.setDescription(rs.getString("description"));
                projet.setCreatedAt(rs.getTimestamp("created_at"));
                projet.setUserNom(rs.getString("user_nom"));
                projets.add(projet);
            }
        }
        return projets;
    }

    public List<Projet> showByUser(int userId) throws SQLException {
        List<Projet> projets = new ArrayList<>();
        String req = "SELECT p.id, p.nom, p.description, p.created_at, u.nom AS user_nom " +
                "FROM Projects p " +
                "JOIN Users u ON p.created_by = u.id " +
                "WHERE p.created_by = ?";
        try (PreparedStatement ps = cnx.prepareStatement(req)) {
            ps.setInt(1, userId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    Projet projet = new Projet();
                    projet.setId(rs.getInt("id"));
                    projet.setNom(rs.getString("nom"));
                    projet.setDescription(rs.getString("description"));
                    projet.setCreatedAt(rs.getTimestamp("created_at"));
                    projet.setUserNom(rs.getString("user_nom"));
                    projets.add(projet);
                }
            }
        }
        return projets;
    }
    public List<Projet> searchProjectsByUserEmail(String email) throws SQLException {
        List<Projet> projets = new ArrayList<>();
        String req = "SELECT p.*, u.nom AS user_nom " +
                "FROM projects p " +
                "JOIN users u ON p.created_by = u.id " +
                "WHERE u.email LIKE ?";

        try (PreparedStatement ps = cnx.prepareStatement(req)) {
            ps.setString(1, "%" + email + "%");

            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    Projet projet = new Projet();
                    // Récupération des données de la table projects
                    projet.setId(rs.getInt("id"));
                    projet.setNom(rs.getString("nom"));
                    projet.setDescription(rs.getString("description"));
                    projet.setCreatedAt(rs.getTimestamp("created_at"));

                    // Récupération du nom de l'utilisateur depuis la jointure
                    projet.setUserNom(rs.getString("user_nom"));

                    projets.add(projet);
                }
            }
        }
        return projets;
    }

    public List<Projet> showCompletedProjects() throws SQLException {
        List<Projet> projets = new ArrayList<>();
        String req = "SELECT p.*, u.nom AS user_nom " +
                "FROM projects p " +
                "JOIN users u ON p.created_by = u.id " +
                "WHERE NOT EXISTS (" +
                "   SELECT 1 FROM missions m " +
                "   WHERE m.project_id = p.id AND m.status <> 'Done'" + // Utilisation de '<>' pour compatibilité SQL
                ")";
        try (Statement st = cnx.createStatement();
             ResultSet rs = st.executeQuery(req)) {
            while (rs.next()) {
                Projet projet = new Projet();
                projet.setId(rs.getInt("id"));
                projet.setNom(rs.getString("nom"));
                projet.setDescription(rs.getString("description"));
                projet.setCreatedAt(rs.getTimestamp("created_at"));
                projet.setUserNom(rs.getString("user_nom"));
                projets.add(projet);
            }
        }
        return projets;
    }

    public List<User> getAllUsers() throws SQLException {
        List<User> users = new ArrayList<>();
        String req = "SELECT id, nom FROM users";
        try (Statement st = cnx.createStatement();
             ResultSet rs = st.executeQuery(req)) {
            while (rs.next()) {
                users.add(new User(
                        rs.getInt("id"),
                        rs.getString("nom")
                ));
            }
        }
        return users;
    }
}