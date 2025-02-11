package tn.nexus.Utils;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;

import io.github.cdimascio.dotenv.Dotenv;

public class DBConnection {
    Dotenv dotenv = Dotenv.configure().ignoreIfMissing().load();
    private final String URL = dotenv.get("DB_URL", "jdbc:mysql://localhost:3306/optirh");
    private final String USER = dotenv.get("DB_USER", "root");
    private final String PWD = dotenv.get("DB_PWD", "");

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
