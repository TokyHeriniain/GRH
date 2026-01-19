import React, { useState } from "react";
import { NavLink, useNavigate } from "react-router-dom";
import { Container, Row, Col, Button, Card, Navbar, Nav, Badge } from "react-bootstrap";
import { FiLogOut, FiUser, FiHome, FiUsers, FiFileText, FiMenu } from "react-icons/fi";
import { FaUserShield } from "react-icons/fa";
import { useAuth } from "../contexts/AuthContext";
//import nyLogo from "../images/ny-havana-logo.png"; // remplacez par le bon chemin vers votre logo

// Palette Ny Havana (exemple, adaptez si besoin)
const COLORS = {
  primary: "#003A63",
  accent: "#D4AF37",
  neutralBg: "#F6F8FA",
  cardBg: "#FFFFFF",
};

function Sidebar({ collapsed, onToggle }) {
  const { user } = useAuth();

  // sécurité : si user non disponible, on affiche placeholder
  const role = user?.role?.name || "Guest";

  return (
    <aside
      style={{
        width: collapsed ? 72 : 260,
        minWidth: collapsed ? 72 : 260,
        height: "100vh",
        background: `linear-gradient(180deg, ${COLORS.primary}, #002A47)`,
        color: "#fff",
        padding: collapsed ? "1rem 0.5rem" : "1.2rem",
        transition: "width 220ms ease-in-out",
        display: "flex",
        flexDirection: "column",
      }}
    >
      <div className="d-flex align-items-center mb-4" style={{ gap: collapsed ? 8 : 12 }}>
        <img
          src={"/images/ny-havana-logo.png"}
          alt="Ny Havana"
          style={{ width: collapsed ? 36 : 48, height: "auto", borderRadius: 6, objectFit: "contain" }}
        />
        {!collapsed && (
          <div>
            <h5 style={{ margin: 0, fontWeight: 700 }}>Ny Havana</h5>
            <small style={{ opacity: 0.85 }}>Gestion des congés</small>
          </div>
        )}
      </div>

      <nav style={{ flex: 1 }}>
        <ul className="list-unstyled" style={{ paddingLeft: 0 }}>
          <li className="mb-2">
            <NavLink to="/dashboard" className="text-white d-flex align-items-center p-2 rounded" style={{ textDecoration: 'none' }}>
              <FiHome size={18} className="me-2" />
              {!collapsed && <span>Dashboard</span>}
            </NavLink>
          </li>

          <li className="mb-2">
            <NavLink to="/profil" className="text-white d-flex align-items-center p-2 rounded" style={{ textDecoration: 'none' }}>
              <FiUser size={18} className="me-2" />
              {!collapsed && <span>Profil</span>}
            </NavLink>
          </li>

          {role === "Employe" && (
            <>
              <li className="mb-2">
                <NavLink to="/demande-conge" className="text-white d-flex align-items-center p-2 rounded" style={{ textDecoration: 'none' }}>
                  <FiFileText size={18} className="me-2" />
                  {!collapsed && <span>Demande de congé</span>}
                </NavLink>
              </li>
              <li className="mb-2">
                <NavLink to="/mes-conges" className="text-white d-flex align-items-center p-2 rounded" style={{ textDecoration: 'none' }}>
                  <FiFileText size={18} className="me-2" />
                  {!collapsed && <span>Mes congés</span>}
                </NavLink>
              </li>
            </>
          )}

          {(role === "Admin" || role === "Manager") && (
            <>
              <li className="mb-2">
                <NavLink to="/manager-conge" className="text-white d-flex align-items-center p-2 rounded" style={{ textDecoration: 'none' }}>
                  <FiFileText size={18} className="me-2" />
                  {!collapsed && <span>Liste congés</span>}
                </NavLink>
              </li>
            </>
          )}

          {role === "Admin" && (
            <>
              <li className="mt-3">
                <hr style={{ borderColor: "rgba(255,255,255,0.08)" }} />
              </li>
              <li className="mb-2">
                <NavLink to="/admin/users" className="text-white d-flex align-items-center p-2 rounded" style={{ textDecoration: 'none' }}>
                  <FiUsers size={18} className="me-2" />
                  {!collapsed && <span>Utilisateurs</span>}
                </NavLink>
              </li>
            </>
          )}

          {role === "RH" && (
            <>
              <li className="mb-2">
                <NavLink to="/rh" className="text-white d-flex align-items-center p-2 rounded" style={{ textDecoration: 'none' }}>
                  <FaUserShield size={16} className="me-2" />
                  {!collapsed && <span>RH Dashboard</span>}
                </NavLink>
              </li>
            </>
          )}
        </ul>
      </nav>

      <div className="mt-auto" style={{ paddingTop: 12 }}>
        <Button variant="outline-light" size="sm" onClick={onToggle} className="w-100 mb-2">
          <FiMenu className="me-2" /> {!collapsed && "Réduire"}
        </Button>
        {!collapsed ? (
          <div className="d-flex align-items-center gap-2">
            <FiUser />
            <div style={{ flex: 1 }}>
              <div style={{ fontSize: 13, fontWeight: 600 }}>{user?.name}</div>
              <div style={{ fontSize: 11 }}>{user?.role?.name}</div>
            </div>
          </div>
        ) : (
          <div className="text-center" style={{ fontSize: 11 }}>{user?.initials || user?.name?.slice(0,2)?.toUpperCase()}</div>
        )}
      </div>
    </aside>
  );
}

function Topbar({ onToggle }) {
  const { logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <Navbar expand={false} className="px-3" style={{ background: COLORS.neutralBg }}>
      <Container fluid>
        <div className="d-flex align-items-center" style={{ gap: 12 }}>
          <Button variant="light" onClick={onToggle} size="sm">
            <FiMenu />
          </Button>
          <img src={"/images/ny-havana-logo.png"} alt="Ny Havana" style={{ height: 36 }} />
        </div>

        <Nav className="ms-auto d-flex align-items-center" style={{ gap: 12 }}>
          <div className="me-2 text-muted small">Bienvenue</div>
          <Button variant="outline-secondary" size="sm" onClick={handleLogout} title="Se déconnecter">
            <FiLogOut />
          </Button>
        </Nav>
      </Container>
    </Navbar>
  );
}

function KPI({ title, value, delta }) {
  return (
    <Card style={{ borderRadius: 12, boxShadow: '0 4px 12px rgba(0,0,0,0.05)' }} className="h-100">
      <Card.Body>
        <div className="d-flex justify-content-between align-items-start">
          <div>
            <div className="text-muted small">{title}</div>
            <h4 style={{ color: COLORS.primary, marginTop: 6 }}>{value}</h4>
          </div>
          <Badge bg="light" text="dark" style={{ borderRadius: 8 }}>
            {delta}
          </Badge>
        </div>
      </Card.Body>
    </Card>
  );
}

export default function DashboardLayout({ children }) {
  const [collapsed, setCollapsed] = useState(false);

  return (
    <div style={{ display: 'flex', minHeight: '100vh', background: COLORS.neutralBg }}>
      <Sidebar collapsed={collapsed} onToggle={() => setCollapsed(c => !c)} />

      <div style={{ flex: 1 }}>
        <Topbar onToggle={() => setCollapsed(c => !c)} />

        <main style={{ padding: 24 }}>
          {/* Dashboard header */}
          <Row className="mb-4">
            <Col>
              <h3 style={{ color: COLORS.primary, marginBottom: 6 }}>Tableau de bord</h3>
              <p className="text-muted" style={{ margin: 0 }}>Vue d'ensemble — Ny Havana</p>
            </Col>
          </Row>

          {/* KPI */}
          <Row className="g-3 mb-4">
            <Col md={3}><KPI title="Demandes en attente" value={24} delta="+3" /></Col>
            <Col md={3}><KPI title="Congés approuvés (30j)" value={128} delta="+8" /></Col>
            <Col md={3}><KPI title="Employés" value={512} delta="-1" /></Col>
            <Col md={3}><KPI title="Taux validation" value={'92%'} delta={'+2%'} /></Col>
          </Row>

          <Row className="g-3">
            <Col lg={8}>
              <Card style={{ borderRadius: 12 }}>
                <Card.Body style={{ minHeight: 260 }}>
                  <h6>Activité récente</h6>
                  <div className="text-muted small">Placeholder pour graphiques et activités. Utilisez Recharts ou Chart.js ici.</div>
                </Card.Body>
              </Card>
            </Col>

            <Col lg={4}>
              <Card style={{ borderRadius: 12 }}>
                <Card.Body>
                  <h6>Raccourcis</h6>
                  <div className="d-grid gap-2 mt-3">
                    <Button variant="outline-primary">➕ Nouvelle demande</Button>
                    <Button variant="outline-secondary">📥 Importer personnels</Button>
                    <Button variant="outline-warning">⚙️ Paramètres</Button>
                  </div>
                </Card.Body>
              </Card>
            </Col>
          </Row>

          {/* children slot */}
          <div className="mt-4">{children}</div>
        </main>
      </div>
    </div>
  );
}
