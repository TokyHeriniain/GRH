import { FaBars } from "react-icons/fa";
import { FiSun, FiMoon, FiLogOut } from "react-icons/fi";
import { useAuth } from "../../contexts/AuthContext";
import { useNavigate } from "react-router-dom";
import { useEffect, useState } from "react";

const Topbar = ({ collapsed, setCollapsed, onToggleMobile }) => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const [theme, setTheme] = useState("light");

  // Charger le thème au chargement
  useEffect(() => {
    const saved = localStorage.getItem("theme") || "light";
    setTheme(saved);
    document.body.classList.toggle("dark-mode", saved === "dark");
  }, []);

  const toggleTheme = () => {
    const newTheme = theme === "light" ? "dark" : "light";
    setTheme(newTheme);
    localStorage.setItem("theme", newTheme);
    document.body.classList.toggle("dark-mode", newTheme === "dark");
  };

  const handleLogout = async () => {
    await logout();
    navigate("/login");
  };

  return (
    <header
      className="topbar d-flex align-items-center justify-content-between px-3"
      style={{
        height: "65px",
        background: "#004E89",
        borderBottom: "3px solid #E3B505",
        position: "fixed",
        top: 0,
        left: 0,
        right: 0,
        zIndex: 2000,
      }}
    >
      {/* LEFT : Bouton mobile */}
      <div className="d-flex align-items-center gap-2">
        {/* BOUTON MOBILE */}
        <button
          className="btn btn-outline-light d-md-none"
          onClick={() => onToggleMobile(true)}
        >
          <FaBars size={20} />
        </button>

        {/* LOGO */}
        <img
          src="/images/nyhavana-logo.png"
          style={{
            height: "45px",
            width: "45px",
            borderRadius: "50%",
            objectFit: "cover",
          }}
        />
        <h5 className="text-white fw-bold m-0 d-none d-sm-block">
          Ny Havana
        </h5>
      </div>

      {/* RIGHT : Mode sombre + user + logout */}
      <div className="d-flex align-items-center gap-3 text-white">

        {/* Toggle Theme */}
        <button
          className="btn btn-outline-light rounded-circle"
          onClick={toggleTheme}
        >
          {theme === "light" ? <FiMoon size={18} /> : <FiSun size={18} />}
        </button>

        {/* USER Bloc */}
        <div className="d-flex flex-column text-end">
          <span className="fw-bold">{user?.name}</span>
          <span className="small text-warning">{user?.role?.name}</span>
        </div>

        {/* Logout */}
        <button className="btn btn-danger px-3" onClick={handleLogout}>
          <FiLogOut className="me-1" />
          Déconnexion
        </button>
      </div>
    </header>
  );
};

export default Topbar;
