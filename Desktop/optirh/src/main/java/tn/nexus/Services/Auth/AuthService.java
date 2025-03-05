package tn.nexus.Services.Auth;

import org.mindrot.jbcrypt.BCrypt;

import tn.nexus.Entities.User;
import tn.nexus.Services.UserService;

import java.sql.*;

public class AuthService {
    private static UserService userService = new UserService();

    public static User loginUser(String email, String motDePasse) {
        try {
            // Use UserService to fetch user by email
            User user = userService.getUserByEmail(email);

            System.out.println(user);
            if (user != null && BCrypt.checkpw(motDePasse, user.getMotDePasse())) {
                // User is authenticated
                UserSession.getInstance().setUser(user);
                return user;
            } else {
                // Invalid email or password
                System.out.println("Invalid email or password.");
                return null;
            }
        } catch (SQLException e) {
            System.out.println("An error occurred during login: " + e.getMessage());
            return null;
        }
    }
}
