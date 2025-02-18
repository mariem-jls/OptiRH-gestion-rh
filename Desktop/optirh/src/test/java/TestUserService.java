
import java.sql.SQLException;
import java.util.List;

import tn.nexus.Entities.User;
import tn.nexus.Services.UserService;

public class TestUserService {
    public static void main(String[] args) {
        UserService us = new UserService();

        // Test insert
        try {
            User u = new User("Ahmed Trabelsi", "ahmed@gmail.com", "123456", "Administrateur", "tunis");
            int result = us.insert(u);
            System.out.println("Insert result: " + result);
        } catch (SQLException e) {
            e.printStackTrace();
        }

        // Test showAll
        try {
            List<User> users = us.showAll();
            System.out.println("Users:");
            for (User user : users) {
                System.out.println(user);
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }

        // Test update
        try {
            User u = new User(3, "Ahmed Trabelsi", "ahmed@gmail.com", "updatedPassword", "Administrateur", "test");
            int result = us.update(u);
            System.out.println("Update result: " + result);
        } catch (SQLException e) {
            e.printStackTrace();
        }

        // Test delete
        try {
            User u = new User();
            u.setId(3); // Assuming the user with ID 3 exists
            int result = us.delete(u);
            System.out.println("Delete result: " + result);
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

}
