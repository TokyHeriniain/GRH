import { NavLink, useNavigate } from "react-router-dom";
import { useAuth } from "../contexts/AuthContext";
import {
  FiLogOut,
  FiUser,
  FiHome,
  FiUsers,
  FiFileText,
  FiLayers,
  FiMenu,
  FiChevronDown,
} from "react-icons/fi";
import { FaUserShield } from "react-icons/fa";
import { Image } from "react-bootstrap";
import { useNavigation } from "./NavigationContext";

const Sidebar = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const {
    collapsed,
    setCollapsed,
    mobileOpen,
    setMobileOpen,
    openStructure,
    setOpenStructure,
    openRH,
    setOpenRH,
  } = useNavigation();

  const handleLogout = async () => {
    await logout();
    navigate("/login");
  };

  if (!user) return null;

  return (
    <>
      <div
        className={`text-white sidebar-container 
          ${collapsed ? "collapsed" : ""} 
          ${mobileOpen ? "sidebar-open" : ""}`}
        style={{
          width: collapsed ? "90px" : "260px",
          transition: "0.3s",
          background: "#161616",
          borderRight: "3px solid #B30000",
          minHeight: "100vh",
          position: "fixed",
          zIndex: 1300,
          top: "65px",
        }}
      >
        {/* Header Sidebar */}
        <div
          className="d-flex align-items-center justify-content-between px-3 py-3 border-bottom border-danger"
          style={{ minHeight: "90px" }}
        >
          {!collapsed && (
            <div className="d-flex flex-column align-items-center w-100">
              <Image
                src="/images/ny-havana-logo.png"
                roundedCircle
                style={{ width: "65px", height: "65px", objectFit: "cover" }}
              />
              <h6 className="mt-2 fw-bold" style={{ color: "#B30000" }}>
                Ny Havana
              </h6>
            </div>
          )}

          <button
            onClick={() => setCollapsed(!collapsed)}
            className="btn btn-sm btn-outline-light"
          >
            <FiMenu size={20} />
          </button>
        </div>

        {/* MENU */}
        <ul className="nav nav-pills flex-column p-2" style={{ gap: "5px" }}>   
          {/* Admin */}
          {user.role?.name === "Admin" && (
            <>
              <li>
                <NavLink
                  to="/dashboard"
                  className="nav-link d-flex align-items-center text-white"
                  style={({ isActive }) => ({
                    background: isActive ? "#B30000" : "transparent",
                    borderRadius: "10px",
                    padding: "10px",
                  })}
                >
                  <FiHome className="me-2" />
                  {!collapsed && "Dashboard"}
                </NavLink>
              </li>
              <li>
                <button
                  className="nav-link d-flex w-100 text-white"
                  onClick={() => setOpenRH(!openRH)}
                >
                  <FaUserShield className="me-2" />
                  {!collapsed && (
                    <>
                      RH Dashboard
                      <FiChevronDown
                        className="ms-auto"
                        style={{
                          transform: openRH ? "rotate(180deg)" : "",
                          transition: "0.2s",
                        }}
                      />
                    </>
                  )}
                </button>

                <div className={`collapse ${openRH ? "show" : ""} ms-4`}>
                  <NavLink to="/rh" className="nav-link text-white">
                    • Gestion des personnels
                  </NavLink>
                  <NavLink to="/rh/conges" className="nav-link text-white">
                    • Gestion des congés
                  </NavLink>
                  <NavLink to="/rh/cloture-annuelle" className="nav-link text-white">
                    • Cloture Annuelle 
                  </NavLink>
                  <NavLink to="/rh/journalRH" className="nav-link text-white">
                    • Journal RH 
                  </NavLink>
                </div>
              </li>
              <li>
                <NavLink to="/admin/users" className="nav-link d-flex text-white">
                  <FiUsers className="me-2" />
                  {!collapsed && "Gestion Utilisateurs"}
                </NavLink>
              </li>
              <li>
                <NavLink to="/admin/rolepermission" className="nav-link d-flex text-white">
                  <FiUsers className="me-2" />
                  {!collapsed && "Gestion Rôles & Permissions"}
                </NavLink>
              </li>
              <li>
                <button
                  className="nav-link d-flex text-white w-100"
                  onClick={() => setOpenStructure(!openStructure)}
                >
                  🏗️
                  {!collapsed && (
                    <>
                      <span className="ms-2">Structure</span>
                      <FiChevronDown
                        className="ms-auto"
                        style={{
                          transform: openStructure ? "rotate(180deg)" : "",
                          transition: "0.2s",
                        }}
                      />
                    </>
                  )}
                </button>

                <div className={`collapse ${openStructure ? "show" : ""} ms-4`}>
                  <NavLink to="/structure" className="nav-link text-white">
                    • Directions
                  </NavLink>
                  <NavLink to="/services" className="nav-link text-white">
                    • Services
                  </NavLink>
                  <NavLink to="/fonctions" className="nav-link text-white">
                    • Fonctions
                  </NavLink>
                </div>
              </li>

              <li>
                <NavLink to="/gestion-jours-feries" className="nav-link text-white">
                  📅 {!collapsed && "Jours fériés"}
                </NavLink>
              </li>
              <li>
                <NavLink
                  to="/profil"
                  className="nav-link d-flex align-items-center text-white"
                >
                  <FiUser className="me-2" />
                  {!collapsed && "Mon Profil"}
                </NavLink>
              </li>
            </>
          )}                
          {/* Employé */}
          {user.role?.name === "Employe" && (
            <>
              <li>
                <NavLink
                  to="/dashboard-employe"
                  className="nav-link d-flex align-items-center text-white"
                  style={({ isActive }) => ({
                    background: isActive ? "#B30000" : "transparent",
                    borderRadius: "10px",
                    padding: "10px",
                  })}
                >
                  <FiHome className="me-2" />
                  {!collapsed && "Dashboard"}
                </NavLink>
              </li>
              <li>
                <NavLink to="/demande-conge" className="nav-link d-flex text-white">
                  <FiFileText className="me-2" />
                  {!collapsed && "Demande de congé"}
                </NavLink>
              </li>
              <li>
                <NavLink to="/mes-conges" className="nav-link d-flex text-white">
                  <FiLayers className="me-2" />
                  {!collapsed && "Mes congés"}
                </NavLink>
              </li>
              <li>
                <NavLink
                  to="/profil"
                  className="nav-link d-flex align-items-center text-white"
                >
                  <FiUser className="me-2" />
                  {!collapsed && "Mon Profil"}
                </NavLink>
              </li>
            </>
          )}
          {/* RH */}
          {user.role?.name === "RH" && (
            <>
              <li>
                <button
                  className="nav-link d-flex w-100 text-white"
                  onClick={() => setOpenRH(!openRH)}
                >
                  <FaUserShield className="me-2" />
                  {!collapsed && (
                    <>
                      RH Dashboard
                      <FiChevronDown
                        className="ms-auto"
                        style={{
                          transform: openRH ? "rotate(180deg)" : "",
                          transition: "0.2s",
                        }}
                      />
                    </>
                  )}
                </button>

                <div className={`collapse ${openRH ? "show" : ""} ms-4`}>
                  <NavLink to="/rh" className="nav-link text-white">
                    • Gestion des personnels
                  </NavLink>
                  <NavLink to="/rh/conges" className="nav-link text-white">
                    • Gestion des congés
                  </NavLink>
                </div>
              </li>
              <li>
                <button
                  className="nav-link d-flex text-white w-100"
                  onClick={() => setOpenStructure(!openStructure)}
                >
                  🏗️
                  {!collapsed && (
                    <>
                      <span className="ms-2">Structure</span>
                      <FiChevronDown
                        className="ms-auto"
                        style={{
                          transform: openStructure ? "rotate(180deg)" : "",
                          transition: "0.2s",
                        }}
                      />
                    </>
                  )}
                </button>

                <div className={`collapse ${openStructure ? "show" : ""} ms-4`}>
                  <NavLink to="/structure" className="nav-link text-white">
                    • Directions
                  </NavLink>                 
                </div>
              </li>
              <li>
                <NavLink
                  to="/profil"
                  className="nav-link d-flex align-items-center text-white"
                >
                  <FiUser className="me-2" />
                  {!collapsed && "Mon Profil"}
                </NavLink>
              </li>
            </>
          )}
          {/* Manager/Admin */}
          {(user.role?.name === "Manager") && (
            <>             
              <li>
                <NavLink
                  to="/manager/dashboard"
                  className="nav-link d-flex align-items-center text-white"
                  style={({ isActive }) => ({
                    background: isActive ? "#B30000" : "transparent",
                    borderRadius: "10px",
                    padding: "10px",
                  })}
                >
                  <FiHome className="me-2" />
                  {!collapsed && "Dashboard"}
                </NavLink>
              </li>
              <li>
                <NavLink to="/demande-conge" className="nav-link d-flex text-white">
                  <FiFileText className="me-2" />
                  {!collapsed && "Demande de congé"}
                </NavLink>
              </li>
              <li>
                <NavLink to="/mes-conges" className="nav-link d-flex text-white">
                  <FiLayers className="me-2" />
                  {!collapsed && "Mes congés"}
                </NavLink>
              </li>                            
              <li>
                <NavLink to="/manager/conges" className="nav-link d-flex text-white">
                  <FiLayers className="me-2" />
                  {!collapsed && "Congés équipe"}
                </NavLink>
              </li>
              <li>
                <NavLink
                  to="/profil"
                  className="nav-link d-flex align-items-center text-white"
                >
                  <FiUser className="me-2" />
                  {!collapsed && "Mon Profil"}
                </NavLink>
              </li>
            </>
          )}                  
        </ul>
        {/* FOOTER */}
        <div className="mt-auto p-3 border-top border-secondary">
          {!collapsed && (
            <>
              <div className="d-flex align-items-center text-white mb-2">
                <FiUser className="me-2" />
                <strong>{user.name}</strong>
              </div>

              <span className="badge bg-danger">{user.role?.name}</span>
            </>
          )}

          <button className="btn btn-outline-danger w-100 mt-3" onClick={handleLogout}>
            <FiLogOut className="me-2" />
            {!collapsed && "Déconnexion"}
          </button>
        </div>
      </div>

      {/* Overlay mobile */}
      {mobileOpen && (
        <div
          className="mobile-overlay"
          onClick={() => setMobileOpen(false)}
          style={{
            position: "fixed",
            inset: 0,
            background: "rgba(0,0,0,0.5)",
            zIndex: 1200,
          }}
        ></div>
      )}
    </>
  );
};

export default Sidebar;
