package tn.nexus.Services;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

import org.mindrot.jbcrypt.BCrypt;

import tn.nexus.Entities.User;
import tn.nexus.Utils.DBConnection;
import tn.nexus.Utils.Enums.Role;

public class UserService implements CRUD<User> {

    private Connection cnx = DBConnection.getInstance().getConnection();
    private Statement st;
    private PreparedStatement ps;

    @Override
    public int insert(User user) throws SQLException {

        String req = "INSERT INTO Users(nom, email, motDePasse, role, address) VALUES (?, ?, ?, ?, ?)";

        ps = cnx.prepareStatement(req);

        ps.setString(1, user.getNom());
        ps.setString(2, user.getEmail());
        ps.setString(3, BCrypt.hashpw(user.getMotDePasse(), BCrypt.gensalt()));
        ps.setString(4, user.getRole().name());
        ps.setString(5, user.getAddress());

        return ps.executeUpdate();
    }

    @Override
    public int update(User person) throws SQLException {
        String req = "UPDATE Users SET nom = ?, email = ?, motDePasse = ?, role = ?, address = ? WHERE id = ?";

        ps = cnx.prepareStatement(req);

        ps.setString(1, person.getNom());
        ps.setString(2, person.getEmail());
        ps.setString(3, BCrypt.hashpw(person.getMotDePasse(), BCrypt.gensalt()));
        ps.setString(4, person.getRole().name());
        ps.setString(5, person.getAddress());
        ps.setInt(6, person.getId());

        System.out.println(ps);

        return ps.executeUpdate();
    }

    @Override
    public int delete(User person) throws SQLException {
        String req = "DELETE FROM Users WHERE id = ?";

        ps = cnx.prepareStatement(req);

        ps.setInt(1, person.getId());

        return ps.executeUpdate();
    }

    @Override
    public List<User> showAll() throws SQLException {
        List<User> temp = new ArrayList<>();

        String req = "SELECT * FROM `users`";

        st = cnx.createStatement();

        ResultSet rs = st.executeQuery(req);

        while (rs.next()) {
            User p = new User();

            p.setId(rs.getInt("id"));
            p.setNom(rs.getString("nom"));
            p.setEmail(rs.getString("email"));
            p.setRole(Role.valueOf(rs.getString("role")));
            p.setAddress(rs.getString("address"));

            temp.add(p);
        }

        return temp;
    }
    public User getUserByEmailAndRole(String email, Role role) throws SQLException {
        String req = "SELECT * FROM Users WHERE email = ? AND role = ?";
        try (PreparedStatement ps = cnx.prepareStatement(req)) {
            ps.setString(1, email);
            ps.setString(2, role.name()); // Convertir le rôle en chaîne de caractères
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    User user = new User();
                    user.setId(rs.getInt("id"));
                    user.setNom(rs.getString("nom"));
                    user.setEmail(rs.getString("email"));
                    user.setRole(Role.valueOf(rs.getString("role"))); // Convertir la chaîne en enum Role
                    user.setAddress(rs.getString("address"));
                    return user;
                }
            }
        }
        return null; // Aucun utilisateur trouvé avec cet email et ce rôle
    }
}
