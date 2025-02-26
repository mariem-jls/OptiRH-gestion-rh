package tn.nexus.Services.Mission;

import tn.nexus.Entities.Mission.Mission;
import tn.nexus.Services.CRUD;
import tn.nexus.Utils.DBConnection;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class MissionService implements CRUD<Mission> {
    private Connection cnx = DBConnection.getInstance().getConnection();

    @Override
    public int insert(Mission mission) throws SQLException {
        String req = "INSERT INTO Missions (titre, description, status, project_id, assigned_to, created_at, updated_at, date_terminer) " +
                "VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        try (PreparedStatement ps = cnx.prepareStatement(req)) {
            ps.setString(1, mission.getTitre());
            ps.setString(2, mission.getDescription());
            ps.setString(3, mission.getStatus());
            ps.setInt(4, mission.getProjectId());
            ps.setInt(5, mission.getAssignedTo());
            ps.setTimestamp(6, mission.getCreatedAt());
            ps.setTimestamp(7, mission.getUpdatedAt());
            ps.setTimestamp(8, mission.getDateTerminer()); // Nouvel attribut
            return ps.executeUpdate();
        }
    }

    @Override
    public int update(Mission mission) throws SQLException {
        String req = "UPDATE Missions SET titre = ?, description = ?, status = ?, project_id = ?, assigned_to = ?, updated_at = ?, date_terminer = ? WHERE id = ?";
        try (PreparedStatement ps = cnx.prepareStatement(req)) {
            ps.setString(1, mission.getTitre());
            ps.setString(2, mission.getDescription());
            ps.setString(3, mission.getStatus());
            ps.setInt(4, mission.getProjectId());
            ps.setInt(5, mission.getAssignedTo());
            ps.setTimestamp(6, mission.getUpdatedAt());
            ps.setTimestamp(7, mission.getDateTerminer()); // Nouvel attribut
            ps.setInt(8, mission.getId());
            return ps.executeUpdate();
        }
    }

    @Override
    public int delete(Mission mission) throws SQLException {
        String req = "DELETE FROM Missions WHERE id = ?";
        try (PreparedStatement ps = cnx.prepareStatement(req)) {
            ps.setInt(1, mission.getId());
            return ps.executeUpdate();
        }
    }

    public void updateMissionStatus(int missionId, String newStatus) throws SQLException {
        String query = "UPDATE Missions SET status = ? WHERE id = ?";
        try (PreparedStatement statement = cnx.prepareStatement(query)) {
            statement.setString(1, newStatus);
            statement.setInt(2, missionId);
            statement.executeUpdate();
        }
    }

    @Override
    public List<Mission> showAll() throws SQLException {
        List<Mission> missions = new ArrayList<>();
        String req = "SELECT * FROM Missions";
        try (Statement st = cnx.createStatement();
             ResultSet rs = st.executeQuery(req)) {
            while (rs.next()) {
                Mission mission = new Mission(
                        rs.getInt("id"),
                        rs.getString("titre"),
                        rs.getString("description"),
                        rs.getString("status"),
                        rs.getInt("project_id"),
                        rs.getInt("assigned_to"),
                        rs.getTimestamp("created_at"),
                        rs.getTimestamp("updated_at"),
                        rs.getTimestamp("date_terminer") // Nouvel attribut
                );
                missions.add(mission);
            }
        }
        return missions;
    }

    // Récupérer les tâches par statut
    public List<Mission> getTasksByStatus(String status) throws SQLException {
        List<Mission> missions = new ArrayList<>();
        String req = "SELECT * FROM Missions WHERE status = ?";
        try (PreparedStatement ps = cnx.prepareStatement(req)) {
            ps.setString(1, status);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    Mission mission = new Mission(
                            rs.getInt("id"),
                            rs.getString("titre"),
                            rs.getString("description"),
                            rs.getString("status"),
                            rs.getInt("project_id"),
                            rs.getInt("assigned_to"),
                            rs.getTimestamp("created_at"),
                            rs.getTimestamp("updated_at"),
                            rs.getTimestamp("date_terminer") // Nouvel attribut
                    );
                    missions.add(mission);
                }
            }
        }
        return missions;
    }

    public void assignMissionToUser(int missionId, int userId) throws SQLException {
        String query = "UPDATE Missions SET assigned_to = ? WHERE id = ?";
        try (PreparedStatement statement = cnx.prepareStatement(query)) {
            statement.setInt(1, userId);
            statement.setInt(2, missionId);
            statement.executeUpdate();
        }
    }

    public void addMission(Mission mission) throws SQLException {
        String query = "INSERT INTO Missions (titre, description, status, project_id, assigned_to, created_at, updated_at, date_terminer) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        try (PreparedStatement statement = cnx.prepareStatement(query)) {
            statement.setString(1, mission.getTitre());
            statement.setString(2, mission.getDescription());
            statement.setString(3, mission.getStatus());
            statement.setInt(4, mission.getProjectId());
            statement.setInt(5, mission.getAssignedTo());
            statement.setTimestamp(6, mission.getCreatedAt());
            statement.setTimestamp(7, mission.getUpdatedAt());
            statement.setTimestamp(8, mission.getDateTerminer()); // Nouvel attribut
            statement.executeUpdate();
        }
    }

    public void update2(Mission mission) throws SQLException {
        String query = "UPDATE Missions SET status = ?, date_terminer = ? WHERE id = ?";
        try (PreparedStatement preparedStatement = cnx.prepareStatement(query)) {
            preparedStatement.setString(1, mission.getStatus());
            preparedStatement.setTimestamp(2, mission.getDateTerminer()); // Nouvel attribut
            preparedStatement.setInt(3, mission.getId());
            preparedStatement.executeUpdate();
        }
    }

    public List<Mission> getTasksByStatusAndProject(String status, int projetId) throws SQLException {
        String query = "SELECT * FROM Missions WHERE status = ? AND project_id = ?";
        try (PreparedStatement statement = cnx.prepareStatement(query)) {
            statement.setString(1, status);
            statement.setInt(2, projetId);
            ResultSet resultSet = statement.executeQuery();

            List<Mission> missions = new ArrayList<>();
            while (resultSet.next()) {
                Mission mission = new Mission(
                        resultSet.getInt("id"),
                        resultSet.getString("titre"),
                        resultSet.getString("description"),
                        resultSet.getString("status"),
                        resultSet.getInt("project_id"),
                        resultSet.getInt("assigned_to"),
                        resultSet.getTimestamp("created_at"),
                        resultSet.getTimestamp("updated_at"),
                        resultSet.getTimestamp("date_terminer") // Nouvel attribut
                );
                missions.add(mission);
            }
            return missions;
        }
    }

    public List<Mission> getTasksByProjectAndStatus(int projetId, String status) throws SQLException {
        List<Mission> missions = new ArrayList<>();
        String query = "SELECT * FROM Missions WHERE project_id = ? AND status = ?";
        try (PreparedStatement preparedStatement = cnx.prepareStatement(query)) {
            preparedStatement.setInt(1, projetId);
            preparedStatement.setString(2, status);
            ResultSet resultSet = preparedStatement.executeQuery();
            while (resultSet.next()) {
                Mission mission = new Mission(
                        resultSet.getInt("id"),
                        resultSet.getString("titre"),
                        resultSet.getString("description"),
                        resultSet.getString("status"),
                        resultSet.getInt("project_id"),
                        resultSet.getInt("assigned_to"),
                        resultSet.getTimestamp("created_at"),
                        resultSet.getTimestamp("updated_at"),
                        resultSet.getTimestamp("date_terminer") // Nouvel attribut
                );
                missions.add(mission);
            }
        }
        return missions;
    }

    public List<Mission> getTasksWithDateTerminedAndStatusNotDone() throws SQLException {
        List<Mission> missions = new ArrayList<>();
        String query = "SELECT * FROM Missions WHERE date_terminer < NOW() AND status != 'Done'";
        try (PreparedStatement statement = cnx.prepareStatement(query)) {
            ResultSet resultSet = statement.executeQuery();
            while (resultSet.next()) {
                Mission mission = new Mission(
                        resultSet.getInt("id"),
                        resultSet.getString("titre"),
                        resultSet.getString("description"),
                        resultSet.getString("status"),
                        resultSet.getInt("project_id"),
                        resultSet.getInt("assigned_to"),
                        resultSet.getTimestamp("created_at"),
                        resultSet.getTimestamp("updated_at"),
                        resultSet.getTimestamp("date_terminer")
                );
                missions.add(mission);
            }
        }
        return missions;
    }
    public List<Mission> getTasksByUserAndStatus(int userId, String status) throws SQLException {
        List<Mission> missions = new ArrayList<>();
        String query = "SELECT * FROM Missions WHERE assigned_to = ? AND status = ?";
        try (PreparedStatement statement = cnx.prepareStatement(query)) {
            statement.setInt(1, userId);
            statement.setString(2, status);
            ResultSet resultSet = statement.executeQuery();
            while (resultSet.next()) {
                Mission mission = new Mission(
                        resultSet.getInt("id"),
                        resultSet.getString("titre"),
                        resultSet.getString("description"),
                        resultSet.getString("status"),
                        resultSet.getInt("project_id"),
                        resultSet.getInt("assigned_to"),
                        resultSet.getTimestamp("created_at"),
                        resultSet.getTimestamp("updated_at"),
                        resultSet.getTimestamp("date_terminer")
                );
                missions.add(mission);
            }
        }
        return missions;
    }


}
