
import java.sql.SQLException;
import java.util.List;

import org.junit.jupiter.api.Order;
import org.junit.jupiter.api.Test;

import tn.nexus.Entities.User;
import tn.nexus.Services.UserService;
import tn.nexus.Utils.DBConnection;

import static org.junit.jupiter.api.Assertions.assertEquals;

public class TestUserService {
    @Test
    @Order(1)
    public void testInsert() {
        UserService userService = new UserService();
        try {
            DBConnection.getInstance().getConnection().prepareStatement("ALTER TABLE users AUTO_INCREMENT = 1;")
                    .executeUpdate();
        } catch (SQLException e1) {
            e1.printStackTrace();
        }
        User user = new User("John Doe", "john@example.com", "password123", "Administrateur", "123 Street");
        try {
            int result = userService.insert(user);
            assertEquals(1, result);
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    @Test
    @Order(2)
    public void testUpdate() {
        UserService userService = new UserService();
        User user = new User(1, "John Doe", "john@example.com", "newpassword", "Administrateur", "123 Street");
        try {
            int result = userService.update(user);
            assertEquals(1, result);
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    @Test
    @Order(3)
    public void testShowAll() {
        UserService userService = new UserService();
        try {
            List<User> users = userService.showAll();
            assertEquals(1, users.size());
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    @Test
    @Order(4)
    public void testDelete() {
        UserService userService = new UserService();
        User user = new User(1, "John Doe", "john@example.com", "newpassword", "Administrateur", "123 Street");
        try {
            int result = userService.delete(user);
            assertEquals(1, result);
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }
}
