import React from "react";
import { Button, ButtonGroup, Card, Col, Form, InputGroup, Row } from "react-bootstrap";

export default function CongeFilters({ search, setSearch, filter, setFilter }) {
  return (
    <Card className="mb-4 shadow-sm border-0 rounded-3">
      <Card.Header className="bg-light fw-bold">🔍 Filtres</Card.Header>
      <Card.Body>
        <Row className="g-3 align-items-center">
          <Col md={4}>
            <InputGroup>
              <InputGroup.Text><i className="bi bi-search" /></InputGroup.Text>
              <Form.Control
                type="text"
                placeholder="Rechercher..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
              />
            </InputGroup>
          </Col>

          <Col md={3}>
            <Form.Select value={filter} onChange={(e) => setFilter(e.target.value)}>
              <option value="all">Tous</option>
              <option value="en_attente">En attente</option>
              <option value="approuve_rh">Validé RH</option>
              <option value="approuve_manager">Validé Manager</option>
              <option value="rejete">Rejeté</option>
            </Form.Select>
          </Col>

          <Col md="auto">
            <Button variant="outline-secondary" onClick={() => { setSearch(""); setFilter("all"); }}>
              Reset
            </Button>
          </Col>         
        </Row>
      </Card.Body>
    </Card>
  );
}
