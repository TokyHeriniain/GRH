import { Card, Row, Col } from "react-bootstrap";

export default function PersonnelInfos({ personnel }) {
  return (
    <Card>
      <Card.Body>
        <Row>
          <Col md={6}><strong>Nom :</strong> {personnel.nom}</Col>
          <Col md={6}><strong>Prénom :</strong> {personnel.prenom}</Col>
          <Col md={6}><strong>Matricule :</strong> {personnel.matricule}</Col>
          <Col md={6}><strong>Date entrée :</strong> {personnel.date_entree}</Col>
          <Col md={6}><strong>Direction :</strong> {personnel.direction?.nom}</Col>
          <Col md={6}><strong>Service :</strong> {personnel.service?.nom}</Col>
          <Col md={6}><strong>Fonction :</strong> {personnel.fonction?.nom}</Col>
        </Row>
      </Card.Body>
    </Card>
  );
}
