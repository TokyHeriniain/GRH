// resources/js/components/layout/DashboardLayout.jsx
import { Container, Row, Col } from "react-bootstrap";
import Sidebar from "./Sidebar";
import Topbar from "./Topbar";
import '@css/dashboard.css';



const DashboardLayout = ({ children }) => {
  return (
    <div className="dashboard-wrapper d-flex">
      <Sidebar />

      <div className="main-content flex-grow-1">
        <Topbar />
        <Container fluid className="p-4 content-area">
          {children}
        </Container>
      </div>
    </div>
  );
};

export default DashboardLayout;
