CREATE TABLE "tbl_quotationmaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_QuotationMaster"
WHERE false;



CREATE TABLE "tbl_quotationdetails_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_QuotationDetails"
WHERE false;



CREATE OR REPLACE FUNCTION fn_backup_quotation_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_quotationmaster_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD; -- ✅ IMPORTANT for delete
ELSE
RETURN NEW; -- ✅ IMPORTANT for update
END IF;
END;
$$
LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION fn_backup_quotation_details()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_quotationdetails_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;


CREATE TRIGGER trg_backup_quotation_master
BEFORE UPDATE OR DELETE
ON "tbl_QuotationMaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_quotation_master();



CREATE TRIGGER trg_backup_quotation_details
BEFORE UPDATE OR DELETE
ON "tbl_QuotationDetails"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_quotation_details();


CREATE TABLE "tbl_proformaInvoiceMaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_proformaInvoiceMaster"
WHERE false;



CREATE TABLE "tbl_proformaInvoiceDetails1_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_proformaInvoiceDetails1"
WHERE false;



CREATE OR REPLACE FUNCTION fn_backup_proforma_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_proformaInvoiceMaster_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION fn_backup_proforma_details()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_proformaInvoiceDetails1_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;




CREATE TRIGGER trg_backup_proforma_master
BEFORE UPDATE OR DELETE
ON "tbl_proformaInvoiceMaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_proforma_master();


CREATE TRIGGER trg_backup_proforma_details
BEFORE UPDATE OR DELETE
ON "tbl_proformaInvoiceDetails1"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_proforma_details();



CREATE TABLE "tbl_SalesOrderMaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_SalesOrderMaster"
WHERE false;


CREATE TABLE "tbl_SalesOrderdetails1_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_SalesOrderdetails1"
WHERE false;



CREATE OR REPLACE FUNCTION fn_backup_salesorder_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_SalesOrderMaster_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION fn_backup_salesorder_details1()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_SalesOrderdetails1_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;




CREATE TRIGGER trg_backup_salesorder_master
AFTER UPDATE OR DELETE
ON "tbl_SalesOrderMaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_salesorder_master();


CREATE TRIGGER trg_backup_salesorder_details1
AFTER UPDATE OR DELETE
ON "tbl_SalesOrderdetails1"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_salesorder_details1();



CREATE TABLE "tbl_DeliveryNoteMaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_DeliveryNoteMaster"
WHERE false;


CREATE TABLE "tbl_DeliveryNoteDetails1_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_DeliveryNoteDetails1"
WHERE false;





CREATE OR REPLACE FUNCTION fn_backup_deliverynote_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_DeliveryNoteMaster_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION fn_backup_deliverynote_details1()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_DeliveryNoteDetails1_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;




CREATE TRIGGER trg_backup_deliverynote_master
AFTER UPDATE OR DELETE
ON "tbl_DeliveryNoteMaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_deliverynote_master();


CREATE TRIGGER trg_backup_deliverynote_details1
AFTER UPDATE OR DELETE
ON "tbl_DeliveryNoteDetails1"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_deliverynote_details1();



CREATE TABLE "tbl_SalesMaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_SalesMaster"
WHERE false;


CREATE TABLE "tbl_SalesDetails1_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_SalesDetails1"
WHERE false;



CREATE OR REPLACE FUNCTION fn_backup_sales_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_SalesMaster_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION fn_backup_sales_details1()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_SalesDetails1_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;



CREATE TRIGGER trg_backup_sales_master
AFTER UPDATE OR DELETE
ON "tbl_SalesMaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_sales_master();


CREATE TRIGGER trg_backup_sales_details1
AFTER UPDATE OR DELETE
ON "tbl_SalesDetails1"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_sales_details1();



CREATE TABLE "tbl_SalesReturnMaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_SalesReturnMaster"
WHERE false;

CREATE TABLE "tbl_SalesReturnDetails1_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_SalesReturnDetails1"
WHERE false;



CREATE OR REPLACE FUNCTION fn_backup_sales_return_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_SalesReturnMaster_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION fn_backup_sales_return_details1()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_SalesReturnDetails1_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;




CREATE TRIGGER trg_backup_sales_return_master
AFTER UPDATE OR DELETE
ON "tbl_SalesReturnMaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_sales_return_master();

CREATE TRIGGER trg_backup_sales_return_details1
AFTER UPDATE OR DELETE
ON "tbl_SalesReturnDetails1"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_sales_return_details1();




CREATE TABLE "tbl_PurchaseOrderMaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_PurchaseOrderMaster"
WHERE false;


CREATE TABLE "tbl_PurchaseOrderDetails1_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_PurchaseOrderDetails1"
WHERE false;




CREATE OR REPLACE FUNCTION fn_backup_purchase_order_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_PurchaseOrderMaster_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;



CREATE OR REPLACE FUNCTION fn_backup_purchase_order_details1()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_PurchaseOrderDetails1_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;



CREATE TRIGGER trg_backup_purchase_order_master
AFTER UPDATE OR DELETE
ON "tbl_PurchaseOrderMaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_purchase_order_master();


CREATE TRIGGER trg_backup_purchase_order_details1
AFTER UPDATE OR DELETE
ON "tbl_PurchaseOrderDetails1"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_purchase_order_details1();



CREATE TABLE "tbl_MaterialReceiptMaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_MaterialReceiptMaster"
WHERE false;


CREATE TABLE "tbl_MaterialReceiptDetails1_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_MaterialReceiptDetails1"
WHERE false;




CREATE OR REPLACE FUNCTION fn_backup_material_receipt_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_MaterialReceiptMaster_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;



CREATE OR REPLACE FUNCTION fn_backup_material_receipt_details1()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_MaterialReceiptDetails1_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;



CREATE TRIGGER trg_backup_material_receipt_master
AFTER UPDATE OR DELETE
ON "tbl_MaterialReceiptMaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_material_receipt_master();


CREATE TRIGGER trg_backup_material_receipt_details1
AFTER UPDATE OR DELETE
ON "tbl_MaterialReceiptDetails1"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_material_receipt_details1();



CREATE TABLE "tbl_Purchasemaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_Purchasemaster"
WHERE false;


CREATE TABLE "tbl_PurchaseDetails1_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_PurchaseDetails1"
WHERE false;



CREATE OR REPLACE FUNCTION fn_backup_purchase_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_Purchasemaster_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION fn_backup_purchase_details1()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_PurchaseDetails1_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;



CREATE TRIGGER trg_backup_purchase_master
AFTER UPDATE OR DELETE
ON "tbl_Purchasemaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_purchase_master();


CREATE TRIGGER trg_backup_purchase_details1
AFTER UPDATE OR DELETE
ON "tbl_PurchaseDetails1"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_purchase_details1();



CREATE TABLE "tbl_PurchaseReturnMaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_PurchaseReturnMaster"
WHERE false;


CREATE TABLE "tbl_PurchaseReturnDetails1_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_PurchaseReturnDetails1"
WHERE false;



CREATE OR REPLACE FUNCTION fn_backup_purchasereturn_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_PurchaseReturnMaster_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;



CREATE OR REPLACE FUNCTION fn_backup_purchasereturn_details1()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_PurchaseReturnDetails1_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;



CREATE TRIGGER trg_backup_purchasereturn_master
AFTER UPDATE OR DELETE
ON "tbl_PurchaseReturnMaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_purchasereturn_master();


CREATE TRIGGER trg_backup_purchasereturn_details1
AFTER UPDATE OR DELETE
ON "tbl_PurchaseReturnDetails1"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_purchasereturn_details1();




CREATE TABLE "tbl_ReceiptMaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_ReceiptMaster"
WHERE false;


CREATE TABLE "tbl_Receiptdetails_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_Receiptdetails"
WHERE false;



CREATE OR REPLACE FUNCTION fn_backup_receipt_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_ReceiptMaster_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;



CREATE OR REPLACE FUNCTION fn_backup_receipt_details()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_Receiptdetails_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;



CREATE TRIGGER trg_backup_receipt_master
AFTER UPDATE OR DELETE
ON "tbl_ReceiptMaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_receipt_master();


CREATE TRIGGER trg_backup_receipt_details
AFTER UPDATE OR DELETE
ON "tbl_Receiptdetails"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_receipt_details();




CREATE TABLE "tbl_PaymentMaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_PaymentMaster"
WHERE false;


CREATE TABLE "tbl_PaymentDetails_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_PaymentDetails"
WHERE false;



CREATE OR REPLACE FUNCTION fn_backup_payment_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_PaymentMaster_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION fn_backup_payment_details()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_PaymentDetails_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;



CREATE TRIGGER trg_backup_payment_master
AFTER UPDATE OR DELETE
ON "tbl_PaymentMaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_payment_master();


CREATE TRIGGER trg_backup_payment_details
AFTER UPDATE OR DELETE
ON "tbl_PaymentDetails"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_payment_details();




CREATE TABLE "tbl_ContraMaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_ContraMaster"
WHERE false;


CREATE TABLE "tbl_ContraDetails_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_ContraDetails"
WHERE false;



CREATE OR REPLACE FUNCTION fn_backup_contra_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_ContraMaster_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;



CREATE OR REPLACE FUNCTION fn_backup_contra_details()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_ContraDetails_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;


CREATE TRIGGER trg_backup_contra_master
AFTER UPDATE OR DELETE
ON "tbl_ContraMaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_contra_master();


CREATE TRIGGER trg_backup_contra_details
AFTER UPDATE OR DELETE
ON "tbl_ContraDetails"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_contra_details();



CREATE TABLE IF NOT EXISTS "tbl_JournalMaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_JournalMaster"
WHERE false;


CREATE TABLE IF NOT EXISTS "tbl_JournalDetails_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_JournalDetails"
WHERE false;


CREATE OR REPLACE FUNCTION fn_backup_journal_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_JournalMaster_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION fn_backup_journal_details()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_JournalDetails_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;


CREATE TRIGGER trg_backup_journal_master
BEFORE UPDATE OR DELETE
ON "tbl_JournalMaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_journal_master();



CREATE TRIGGER trg_backup_journal_details
BEFORE UPDATE OR DELETE
ON "tbl_JournalDetails"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_journal_details();



CREATE TABLE IF NOT EXISTS "tbl_PayableMaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_PayableMaster"
WHERE false;


CREATE TABLE IF NOT EXISTS "tbl_PayableDetails_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_PayableDetails"
WHERE false;


CREATE OR REPLACE FUNCTION fn_backup_payable_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_PayableMaster_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;



CREATE OR REPLACE FUNCTION fn_backup_payable_details()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_PayableDetails_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;



CREATE TRIGGER trg_backup_payable_master
BEFORE UPDATE OR DELETE
ON "tbl_PayableMaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_payable_master();



CREATE TRIGGER trg_backup_payable_details
BEFORE UPDATE OR DELETE
ON "tbl_PayableDetails"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_payable_details();


CREATE TABLE "tbl_ReceivableMaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_ReceivableMaster"
WHERE false;


CREATE TABLE "tbl_ReceivableDetails_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_ReceivableDetails"
WHERE false;



CREATE OR REPLACE FUNCTION fn_backup_receivable_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_ReceivableMaster_backup"
SELECT OLD.*, TG_OP, NOW();

RETURN OLD;
END;
$$
LANGUAGE plpgsql;



CREATE OR REPLACE FUNCTION fn_backup_receivable_details()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_ReceivableDetails_backup"
SELECT OLD.*, TG_OP, NOW();

RETURN OLD;
END;
$$
LANGUAGE plpgsql;



CREATE TRIGGER trg_backup_receivable_master
AFTER UPDATE OR DELETE
ON "tbl_ReceivableMaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_receivable_master();


CREATE TRIGGER trg_backup_receivable_details
AFTER UPDATE OR DELETE
ON "tbl_ReceivableDetails"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_receivable_details();


CREATE TABLE "tbl_PhysicalStockMaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_PhysicalStockMaster"
WHERE false;


CREATE TABLE "tbl_PhysicalStockDetails1_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_PhysicalStockDetails1"
WHERE false;


CREATE OR REPLACE FUNCTION fn_backup_physical_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_PhysicalStockMaster_backup"
SELECT OLD.*, TG_OP, NOW();

RETURN OLD;
END;
$$
LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION fn_backup_physical_details1()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_PhysicalStockDetails1_backup"
SELECT OLD.*, TG_OP, NOW();

RETURN OLD;
END;
$$
LANGUAGE plpgsql;



CREATE TRIGGER trg_backup_physical_master
AFTER UPDATE OR DELETE
ON "tbl_PhysicalStockMaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_physical_master();


CREATE TRIGGER trg_backup_physical_details1
AFTER UPDATE OR DELETE
ON "tbl_PhysicalStockDetails1"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_physical_details1();


CREATE TABLE "tbl_DamageStockMaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_DamageStockMaster"
WHERE false;


CREATE TABLE "tbl_DamageStockDetails1_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_DamageStockDetails1"
WHERE false;



CREATE OR REPLACE FUNCTION fn_backup_damage_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_DamageStockMaster_backup"
SELECT OLD.*, TG_OP, NOW();

RETURN OLD;
END;
$$
LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION fn_backup_damage_details1()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_DamageStockDetails1_backup"
SELECT OLD.*, TG_OP, NOW();

RETURN OLD;
END;
$$
LANGUAGE plpgsql;


CREATE TRIGGER trg_backup_damage_master
AFTER UPDATE OR DELETE
ON "tbl_DamageStockMaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_damage_master();


CREATE TRIGGER trg_backup_damage_details1
AFTER UPDATE OR DELETE
ON "tbl_DamageStockDetails1"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_damage_details1();



CREATE TABLE IF NOT EXISTS "tbl_StockTransferMaster_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_StockTransferMaster"
WHERE false;


CREATE TABLE IF NOT EXISTS "tbl_StockTransferDetails_backup"
AS
SELECT *,
NULL::text AS operation_type,
NULL::timestamp AS backup_time
FROM "tbl_StockTransferDetails"
WHERE false;



CREATE OR REPLACE FUNCTION fn_backup_stocktransfer_master()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_StockTransferMaster_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION fn_backup_stocktransfer_details()
RETURNS TRIGGER AS
$$
BEGIN
INSERT INTO "tbl_StockTransferDetails_backup"
SELECT OLD.*, TG_OP, NOW();

IF TG_OP = 'DELETE' THEN
RETURN OLD;
ELSE
RETURN NEW;
END IF;
END;
$$
LANGUAGE plpgsql;


CREATE TRIGGER trg_backup_stocktransfer_master
BEFORE UPDATE OR DELETE
ON "tbl_StockTransferMaster"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_stocktransfer_master();



CREATE TRIGGER trg_backup_stocktransfer_details
BEFORE UPDATE OR DELETE
ON "tbl_StockTransferDetails"
FOR EACH ROW
EXECUTE FUNCTION fn_backup_stocktransfer_details();
