import { Card, Row, Col, Image, Badge } from "react-bootstrap";

export default function PersonnelInfos({ personnel }) {
  const formatDate = (dateStr) =>
    dateStr ? new Date(dateStr).toLocaleDateString("fr-FR") : "N/A";

  if (!personnel) return null;

  return (
    <Card className="shadow-sm mb-3">
      <Card.Header>
        <strong>Fiche du personnel</strong>
      </Card.Header>

      <Card.Body>
        <Row className="gy-3 align-items-center">
          {/* Photo */}
          <Col xs={12} md={3} className="text-center">
            <Image
              src={personnel.photo || "/default-avatar.png"}
              roundedCircle
              fluid
              style={{ maxWidth: "130px" }}
              alt={`${personnel.nom} ${personnel.prenom}`}
            />
          </Col>

          {/* Infos personnelles */}
          <Col xs={12} md={9}>
            <Row className="gy-2">
              <Col xs={12} md={6}>
                <strong>Nom :</strong> {personnel.nom || "N/A"}
              </Col>
              <Col xs={12} md={6}>
                <strong>Prénom :</strong> {personnel.prenom || "N/A"}
              </Col>
              <Col xs={12} md={6}>
                <strong>Matricule :</strong> {personnel.matricule || "N/A"}
              </Col>
              <Col xs={12} md={6}>
                <strong>Date de naissance :</strong>{" "}
                {formatDate(personnel.date_naissance)}
              </Col>
              <Col xs={12} md={6}>
                <strong>Date d’entrée :</strong>{" "}
                {formatDate(personnel.date_entree)}
              </Col>
              <Col xs={12} md={6}>
                <strong>CIN :</strong> {personnel.cin || "N/A"}
              </Col>
              <Col xs={12}>
                <strong>Adresse :</strong> {personnel.adresse || "N/A"}
              </Col>
              <Col xs={12}>
                <strong>Diplôme :</strong> {personnel.diplome || "N/A"}
              </Col>

              {/* Organisation */}
              <Col xs={12} md={4}>
                <strong>Direction :</strong>{" "}
                {personnel.direction?.nom ? (
                  <Badge bg="primary">{personnel.direction.nom}</Badge>
                ) : (
                  "N/A"
                )}
              </Col>
              <Col xs={12} md={4}>
                <strong>Service :</strong>{" "}
                {personnel.service?.nom ? (
                  <Badge bg="info">{personnel.service.nom}</Badge>
                ) : (
                  "N/A"
                )}
              </Col>
              <Col xs={12} md={4}>
                <strong>Fonction :</strong>{" "}
                {personnel.fonction?.nom ? (
                  <Badge bg="secondary">{personnel.fonction.nom}</Badge>
                ) : (
                  "N/A"
                )}
              </Col>
            </Row>
          </Col>
        </Row>
      </Card.Body>
    </Card>
  );
}
