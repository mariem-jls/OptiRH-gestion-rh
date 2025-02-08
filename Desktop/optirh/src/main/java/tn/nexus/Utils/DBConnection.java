package tn.nexus.Utils;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;

public class DBConnection {
    private final String USER = "root";
    private final String PWD = "";
    private final String URL = "jdbc:mysql://localhost:3306/optirh_db";

    public static DBConnection instance;

    private Connection con;

    private DBConnection() {
        try {
            con = DriverManager.getConnection(URL, USER, PWD);
            System.out.println("Connection successful");
        } catch (SQLException e) {
            System.err.println(e.getMessage());
        }
    }

    public static DBConnection getInstance() {
        if (instance == null) {
            instance = new DBConnection();
        }

        return instance;
    }
}
