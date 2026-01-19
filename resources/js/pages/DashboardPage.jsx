// resources/js/pages/DashboardPage.jsx
import DashboardLayout from "../components/layout/DashboardLayout";
import { Card, Row, Col } from "react-bootstrap";
import { PeopleFill, CalendarCheck, PieChartFill } from "react-bootstrap-icons";

const DashboardPage = () => {
  return (
    <DashboardLayout>
      <h3 className="fw-bold mb-4">Vue d’ensemble</h3>

      <Row className="g-4 mb-4">
        <Col md={4}>
          <Card className="stat-card">
            <Card.Body>
              <div className="stat-icon bg-primary">
                <PeopleFill />
              </div>
              <h5 className="stat-title">Employés actifs</h5>
              <h2 className="stat-value">228</h2>
            </Card.Body>
          </Card>
        </Col>

        <Col md={4}>
          <Card className="stat-card">
            <Card.Body>
              <div className="stat-icon bg-warning">
                <CalendarCheck />
              </div>
              <h5 className="stat-title">Congés approuvés</h5>
              <h2 className="stat-value">54</h2>
            </Card.Body>
          </Card>
        </Col>

        <Col md={4}>
          <Card className="stat-card">
            <Card.Body>
              <div className="stat-icon bg-dark text-light">
                <PieChartFill />
              </div>
              <h5 className="stat-title">Taux d’occupation</h5>
              <h2 className="stat-value">92%</h2>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      <Card className="shadow-sm p-4">
        <h5 className="fw-bold mb-3">Activité récente</h5>
        <ul className="recent-list">
          <li>✔ EMP023 - Congé approuvé</li>
          <li>✔ Nouvel employé ajouté</li>
          <li>✔ Import Legacy terminé</li>
        </ul>
      </Card>
    </DashboardLayout>
  );
};

export default DashboardPage;
