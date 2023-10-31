import PlaygroundApp from '@/components/shared/editor';
import { Container } from 'react-bootstrap';

export const RichEditorPage = () => {
  return (
    <div id="content-page" className="content-page">
      <Container>
        <PlaygroundApp />
      </Container>
    </div>
  );
};
