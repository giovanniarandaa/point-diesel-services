import CreateEstimate from '@/pages/estimates/create';
import { type Customer, type Estimate } from '@/types';

interface Props {
    estimate: Estimate;
    customers: Customer[];
}

export default function EditEstimate({ estimate, customers }: Props) {
    return (
        <CreateEstimate
            customers={customers}
            estimate={{
                id: estimate.id,
                customer_id: estimate.customer_id,
                unit_id: estimate.unit_id,
                notes: estimate.notes,
                lines: estimate.lines?.map((l) => ({
                    lineable_type: l.lineable_type,
                    lineable_id: l.lineable_id,
                    description: l.description,
                    quantity: l.quantity,
                    unit_price: l.unit_price,
                })),
            }}
        />
    );
}
