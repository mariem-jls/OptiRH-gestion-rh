package tn.nexus.Services.Auth;

import tn.nexus.Entities.User;
import tn.nexus.Utils.Enums.Role;

public class UserSession {
    private static UserSession instance;
    private User user;

    private UserSession() {
    }

    public static UserSession getInstance() {
        if (instance == null) {
            instance = new UserSession();
        }
        return instance;
    }

    public void setUser(User user) {
        this.user = user;
    }

    public User getUser() {
        return user;
    }

    public void logout() {
        user = null;
    }

    public boolean hasRole(Role role) {
        if (user != null && user.getRole() != null) {
            return user.getRole().equals(role);
        }
        return false;
    }
}
