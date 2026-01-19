// resources/js/components/layout/Sidebar.jsx

import { useState } from "react";
import { NavLink } from "react-router-dom";
import {
  FaBars,
  FaHome,
  FaUserTie,
  FaUsersCog,
  FaUserShield,
} from "react-icons/fa";
import { FiChevronDown } from "react-icons/fi";

const Sidebar = ({ collapsed, setCollapsed, user, onToggleMobile }) => {
  const [openRH, setOpenRH] = useState(false);

  return (
    <>
      {/* OVERLAY MOBILE */}
      <div
        className={`overlay ${collapsed ? "active" : ""}`}
        onClick={() => onToggleMobile(false)}
      />

      <aside className={`sidebar ${collapsed ? "collapsed" : ""}`}>
        {/* LOGO */}
        <div className="brand d-flex align-items-center justify-content-center">
          <img
            src="/images/nyhavana-logo.png"
            style={{ width: collapsed ? 42 : 60, transition: "0.25s" }}
            alt="Ny Havana"
          />
        </div>

        {/* TOGGLE BUTTON */}
        <button
          className="btn text-white w-100 mb-3"
          onClick={() => setCollapsed(!collapsed)}
          style={{ fontSize: "18px" }}
        >
          <FaBars />
        </button>

        {/* MENU */}
        <ul className="list-unstyled">
          {/* Dashboard */}
          <li>
            <NavLink
              to="/dashboard"
              className="nav-link"
              activeclassname="active"
            >
              <FaHome className="me-2" />
              {!collapsed && "Dashboard"}
            </NavLink>
          </li>

          {/* Gestion du personnel */}
          <li>
            <NavLink to="/personnels" className="nav-link">
              <FaUserTie className="me-2" />
              {!collapsed && "Gestion des personnels"}
            </NavLink>
          </li>

          {/* Administration */}
          {user?.role?.name === "Admin" && (
            <li>
              <NavLink to="/admin" className="nav-link">
                <FaUsersCog className="me-2" />
                {!collapsed && "Administration"}
              </NavLink>
            </li>
          )}

          {/* RH */}
          {user?.role?.name === "RH" && (
            <li>
              <button
                className="nav-link d-flex w-100"
                onClick={() => setOpenRH(!openRH)}
              >
                <FaUserShield className="me-2" />
                {!collapsed && (
                  <>
                    RH
                    <FiChevronDown
                      className="ms-auto"
                      style={{
                        transform: openRH ? "rotate(180deg)" : "rotate(0deg)",
                        transition: "0.3s",
                      }}
                    />
                  </>
                )}
              </button>

              {/* Sous-menu RH */}
              {!collapsed && (
                <div
                  className={`ms-4 ${
                    openRH ? "animate__animated animate__fadeInDown" : ""
                  }`}
                >
                  <NavLink to="/rh" className="nav-link small">
                    • Dashboard RH
                  </NavLink>

                  <NavLink to="/rh/conges" className="nav-link small">
                    • Gestion des congés
                  </NavLink>

                  <NavLink to="/rh/personnels" className="nav-link small">
                    • Gestion du personnel RH
                  </NavLink>
                </div>
              )}
            </li>
          )}
        </ul>
      </aside>
    </>
  );
};

export default Sidebar;
